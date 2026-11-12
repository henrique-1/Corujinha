# Security Audit Report — Corujinha (`run-1`)

## 1. Run Scope, Execution Boundary, and Provenance

- **Target Repository**: `/home/henrique_1/Documents/SESI/prepara-avalia+` (`Corujinha`)
- **Source Ref**: `183e937-dirty`
- **Profile / Scope**: `standard` | Scope paths: `.`
- **Budget & Agents**: `budget: null` (unlimited `standard` pass) — **46 subagents** spent across all phases:
  - Phase 1 (Reconnaissance): 4 `research` agents (`1a`, `1b`, `1c`, `1d`)
  - Phase 2 (Hunting & Coverage Critics): 8 `research` hunters (7 Wave 1 + 1 Wave 2) + 2 `research` coverage critics (Post-Wave + Final-Clean)
  - Phase 3 (Candidate Validation): 6 `self` sandbox verifiers (5 retained + 1 malformed JSON output discarded without repair and re-run cleanly via `verifier-auth-mfa-2`)
  - Phase 5 (Final Record Verification): 26 `research` verifiers (1 independent verifier per 25 `confirmed`/`needs_validation` records + 1 fresh re-verifier on a revised record)
- **Prior Runs Used**: None (initial baseline audit)
- **Execution Policy**: `sandboxed-source-and-local-only` — All PHP/Laravel reproduction scripts executed strictly inside `bwrap --unshare-net` with `env -i`, `prlimit`, read-only `/` bind mount (`--ro-bind / /`), writable scratch-only bind mounts, SQLite `:memory:`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, and `BROADCAST_CONNECTION=null`. All 5 verifier proof files were promoted from `scratch/proof.txt` to `agents/<id>/artifacts/proof.txt` via parent-side `promote.mjs` (`O_NOFOLLOW`/`O_EXCL`, regular file, `nlink === 1`). Target repository files remained unmodified.
- **Explicit Out-of-Scope / Unobserved Deployment Coverage**:
  - CloudPanel Nginx reverse proxy vhost (`corujinha.bissoli.tech`), WAF, Fail2ban, TLS termination, HTTP security headers, and proxy `limit_req` rules (not stored in git).
  - Production MariaDB isolation/locking configuration, PHP-FPM worker pool settings (`pm.max_children`), and decrypted `.env.production.encrypted` values (encryption key not available in repository/CI secrets).
  - Live WebSocket TLS termination (`wss://`) and external SMTP delivery infrastructure.

## 2. Security Posture Summary

Across 10 audited coverage units, this audit **confirmed 18 vulnerabilities** (**4 High**, **12 Medium**, **2 Low**), documented **7 unresolved leads** in `NEEDS VALIDATION` requiring owner-observed deployment checks or multi-process concurrency validation, and **rejected 1 candidate** after sandbox verification showed cross-principal isolation held. The highest-risk exposures center on: (1) unauthenticated public Laravel Reverb WebSocket channels (`admin.professores`, `professor.{id}`, `classroom.{id}`) broadcasting professor PII (names, emails, Brazilian CPFs, TOTP status), classroom join codes, question content, and student telemetry; (2) unauthenticated mid-exam answer key and pedagogical feedback exposure in `GET /sala/{code}/resultado` alongside multiple student state/integrity bypasses (`saveAnswer` post-submission mutation, cross-assessment question injection, and `finishReview` limit bypass/unvalidated storage); (3) missing step-up re-authentication for TOTP disablement and Passkey enrollment/deletion plus external leakage of plaintext TOTP enrollment seeds to `api.qrserver.com`; and (4) unauthenticated teacher draft question media IDOR via `GET /midia/questoes/{id}` and nested-input HTTP 500 / CPU amplification vectors.

## 3. Confirmed Findings Summary

| # | Severity | Fingerprint | Title | Affected Boundary | One-Line Observed Result |
|---:|---|---|---|---|---|
| 1 | **HIGH** | `auth/mfa-disable-and-passkey-enroll-missing-step-up` | MFA disablement, Passkey enrollment, and Passkey deletion require no password or TOTP step-up verification | `routes/web.php:76 -> app/Http/Controllers/Auth/SecurityController.php:138` | For a TOTP-enabled user, POST /seguranca/passkey returned HTTP 200 {"success":true,"id":1} and persisted the Passkey, DELETE /seguranca/passkey/1 returned HTTP 302 and deleted r... |
| 2 | **HIGH** | `auth/totp-secret-leaked-via-external-qr-url` | Pending TOTP secret and user email are exfiltrated to third-party api.qrserver.com via QR image URL | `routes/web.php:74 -> app/Http/Controllers/Auth/SecurityController.php:69` | GET /seguranca returned HTTP 200 with <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=otpauth%3A%2F%2Ftotp%2FCorujinha%3Aqr-leak%40example.com%3Fsecret%3... |
| 3 | **HIGH** | `reverb-realtime:public-admin-and-professor-channels-leak-pii-and-codes` | Public Reverb Channels Expose Professor PII and Classroom Access Codes Without Authentication | `app/Http/Controllers/Admin/ProfessorController.php:65 -> vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php:67` | In the `bwrap` sandbox test (`scratch/proof.txt`), `Pusher\Server::open()` accepted `Origin: https://untrusted-attacker.example` (`pusher:connection_established`), `EventHandler... |
| 4 | **HIGH** | `student-player-state/showresult-mid-exam-gabarito-disclosure` | Mid-Exam Answer Key and Feedback Disclosure via Unauthenticated Attempt State in ClassroomPlayerController::showResult | `routes/web.php:37 -> resources/views/estudante/resultado.blade.php:256` | GET /sala/AVAL-TEST/resultado returned HTTP 200 while status=in_progress and classroom_active=true, exposing Gabarito Correto, Expected command feedback secret, Expected clue fe... |
| 5 | **MEDIUM** | `auth/passkey-assertion-missing-uv-flag-downgrades-mfa` | Passkey login bypasses TOTP MFA without verifying the WebAuthn User Verified (UV) flag | `routes/web.php:50 -> app/Http/Controllers/Auth/AuthController.php:150` | In bwrap sandbox (proof.txt TEST 1), POST /login/passkey/options returned HTTP 200 with userVerification=preferred, and POST /login/passkey with flags=0x01 returned HTTP 200 {"s... |
| 6 | **MEDIUM** | `auth/password-reset-leaves-active-sessions-valid` | Password reset does not invalidate active database sessions | `routes/web.php:62 -> routes/web.php:113` | Before reset, session row qSY1isTFz0W0y3RbE93MoCKslMVKzKOCWncA6K8M (user_id=4) accessed GET /professor/salas with HTTP 200. After POST /redefinir-senha returned HTTP 302 and upd... |
| 7 | **MEDIUM** | `auth/totp-code-replay-within-validity-window` | TOTP 6-digit login codes can be replayed across independent sessions within the validity window | `routes/web.php:53 -> app/Services/TotpService.php:35` | Both Session A and Session B submitted identical code 749458 in time slice 59815108, both received HTTP 302 redirects to /professor/salas, and both accessed GET /professor/salas... |
| 8 | **MEDIUM** | `availability/finish-review-n-plus-one-question-queries` | Unvalidated reviews array in ClassroomPlayerController::finishReview executes one SELECT * FROM questions query per arbitrary key in a single request | `routes/web.php:36 -> app/Http/Controllers/Student/ClassroomPlayerController.php:330` | POST /sala/C6-TEST/revisao/concluir returned HTTP 302 and DB::enableQueryLog() recorded 207 total queries in 30.8 ms, including 201 SELECTs from questions (200 sequential 'selec... |
| 9 | **MEDIUM** | `availability/unbounded-markers-highlight-amplification` | Unbounded markers array in saveAnswer amplifies CPU/DOM work in TextHighlightService::applyHighlights() and Blade marker rendering | `routes/web.php:33 -> resources/views/professor/salas/tentativa-detalhes.blade.php:174` | saveAnswer accepted 2,000 markers + 10KB reason with HTTP 200. TextHighlightService::applyHighlights() scaled superlinearly on T=5,000 nodes: M=100 (5,741 B) took 100.91 ms; M=2... |
| 10 | **MEDIUM** | `chain:saveAnswer-unvalidated-markers-to-teacher-tentativa-detalhes-500` | Unvalidated nested markers array in ClassroomPlayerController::saveAnswer causes persistent HTTP 500 on teacher attempt details and student result views | `routes/web.php:33 -> app/Services/TextHighlightService.php:75` | POST /sala/C1-TEST/atividade/responder returned HTTP 200 {"success":true} and persisted [{"kind":"command","text":["nested"]},{"kind":"evidence","text":"ok"}] in student_answers... |
| 11 | **MEDIUM** | `professor-admin-isolation:midia-questao-unauthenticated-idor` | Unauthenticated Sequential IDOR on Private and Draft Question Support Images via /midia/questoes/{question} | `routes/web.php:25 -> app/Http/Controllers/Professor/QuestionController.php:193` | Unauthenticated GET /midia/questoes/5 returned HTTP 200 with Content-Type: image/jpeg and streamed the exact 48-byte dummy image from Teacher B's draft question. |
| 12 | **MEDIUM** | `reverb-realtime:public-classroom-channel-leaks-questions-and-student-telemetry` | Public Classroom Channel `classroom.{id}` Leaks Question Content, Room Access Codes, and Peer Student Telemetry | `app/Http/Controllers/Professor/QuestionController.php:182 -> vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php:67` | In the `bwrap` sandbox test (`scratch/proof.txt`), subscribing without `auth` to `classroom.1` returned `pusher_internal:subscription_succeeded`, and `EventDispatcher::dispatchS... |
| 13 | **MEDIUM** | `student-player-state/cross-assessment-question-injection-score-inflation` | Cross-Assessment and Cross-Tenant Question Injection via Unscoped question_id in ClassroomPlayerController::saveAnswer | `routes/web.php:33 -> app/Models/StudentAttempt.php:47` | All four saveAnswer requests returned HTTP 200 {"success":true}. Attempt2 recorded score=4/2 and earnedPoints=6/4 (max expected 2 and 4.0), and AssessmentController::statistics(... |
| 14 | **MEDIUM** | `student-player-state/finishreview-disabled-and-replay-limit-bypass` | Collective Review Disablement Bypass and Sequential Replay Limit Bypass in finishReview | `routes/web.php:36 -> app/Http/Controllers/Student/ClassroomPlayerController.php:321` | On AVAL-NORE, GET /revisao redirected to resultado, but POST /revisao/concluir returned 302 to resultado with attempt_status=reviewed and score=2. On AVAL-TEST (max=1), Replay 1... |
| 15 | **MEDIUM** | `student-player-state/finishreview-saveanswer-missing-input-validation` | Missing Input Validation in finishReview and Incomplete Nested Validation in saveAnswer Causing Unhandled 500s and Invalid Answer/Marker Storage | `routes/web.php:33 -> resources/views/estudante/resultado.blade.php:121` | finishReview returned HTTP 500 ErrorException on reviews='bad-string', HTTP 500 TypeError on reviewed_answer=['B'], and HTTP 302 storing reviewed_answer='Z' in DB (reviews[qid]=... |
| 16 | **MEDIUM** | `student-player-state/saveanswer-post-submission-state-bypass` | Post-Submission and Post-Review Answer Tampering via Missing Attempt Status Guard in saveAnswer | `routes/web.php:33 -> app/Http/Controllers/Student/ClassroomPlayerController.php:180` | While status='submitted', saveAnswer returned HTTP 200, updated q1 initial_answer='B' and is_correct_final=true (score=1), enabling finishReview to change Q2 as well for score=2... |
| 17 | **LOW** | `auth/passkey-host-header-and-origin-scheme-bypass` | Passkey origin check ignores scheme and port via parse_url(PHP_URL_HOST) and derives rpId from request Host header | `routes/web.php:49 -> app/Services/PasskeyService.php:316` | In bwrap sandbox (proof.txt TEST 2): 2A (HTTPS localhost, origin='http://localhost:9999') returned HTTP 200; 2C (HTTPS corujinha.bissoli.tech, origin='http://corujinha.bissoli.t... |
| 18 | **LOW** | `auth/passkey-sign-count-zero-regression-bypass` | Passkey clone-detection check is bypassed when an assertion reports sign_count=0 against a non-zero stored counter | `routes/web.php:50 -> app/Services/PasskeyService.php:263` | In bwrap sandbox (proof.txt TEST 3): sign_count=4 returned HTTP 422 {"error":"Contador de uso da Passkey inválido (possível credencial clonada)."}, whereas sign_count=0 returned... |

## 4. Confirmed Findings (Detailed Triage & Fix Summary)

### 1. [HIGH] MFA disablement, Passkey enrollment, and Passkey deletion require no password or TOTP step-up verification (`auth/mfa-disable-and-passkey-enroll-missing-step-up`)

- **Repository Source Locations**: `routes/web.php:76`, `app/Http/Controllers/Auth/SecurityController.php:84`, `app/Http/Controllers/Auth/SecurityController.php:116`, `app/Http/Controllers/Auth/SecurityController.php:138`
- **Lower-Trust Principal / Attacker Perspective**: An attacker who obtains an active user session (or temporary access to an unlocked browser) seeks to downgrade MFA or persist access without knowing the victim's password or possessing their TOTP device.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `GET /seguranca/passkey/options`, `POST /seguranca/passkey (id, clientDataJSON, publicKeySpki, publicKeyAlg, name)`, `DELETE /seguranca/passkey/1`, `POST /seguranca/totp/disable`
  - Steps: Authenticate as an active TOTP-enabled professor. Call GET /seguranca/passkey/options and POST /seguranca/passkey with a generated EC P-256 SPKI keypair and zero password/TOTP parameters. Call DELETE /seguranca/passkey/{id} with zero password/TOTP parameters. Call POST /seguranca/totp/disable with zero password/TOTP parameters and check $user->isTotpEnabled().
- **Conditions**: authentication_level: Requires an already-authenticated session (e.g., stolen session cookie, XSS-driven same-origin request, or brief access to an unlocked terminal).
- **Actual Observed Result**: For a TOTP-enabled user, POST /seguranca/passkey returned HTTP 200 {"success":true,"id":1} and persisted the Passkey, DELETE /seguranca/passkey/1 returned HTTP 302 and deleted record id=1, and POST /seguranca/totp/disable returned HTTP 302 and cleared TOTP (isTotpEnabled_after=NO), all with zero password or TOTP re-verification.
- **Impact & Priority Rationale**:
  - *Impact*: Allows silent removal of TOTP protection, deletion of legitimate Passkeys, and enrollment of an attacker-controlled Passkey that bypasses both password and TOTP on future logins.
  - *Likelihood*: Requires an existing authenticated session, after which all MFA downgrade and backdoor enrollment endpoints succeed in a single request.
  - *Overall*: **HIGH** (confidence: **high** — Verified in source and demonstrated via HTTP kernel requests against all three endpoints for a TOTP-enabled user.)
- **Smallest Source Fix**: Require fresh step-up authentication (current password and, if TOTP is enabled, a valid TOTP code, or Laravel's password.confirm middleware combined with an active TOTP check) on disableTotp, passkeyRegisterOptions/passkeyRegisterStore, and passkeyDestroy.

File: `app/Http/Controllers/Auth/SecurityController.php`
```php
public function disableTotp(Request $request, TotpService $totpService): RedirectResponse
{
    $user = Auth::user();
    $request->validate(['current_password' => ['required', 'current_password'], 'totp_code' => ['required', 'string', 'size:6']]);
    if (! $totpService->verify((string) $user->totp_secret, (string) $request->input('totp_code'))) {
        return back()->withErrors(['totp_code' => 'Código TOTP inválido.']);
    }
    $user->update(['totp_secret' => null, 'totp_enabled_at' => null]);
    return redirect()->route('security.index')->with('success', 'Autenticação em duas etapas (TOTP) desativada.');
}
```

### 2. [HIGH] Pending TOTP secret and user email are exfiltrated to third-party api.qrserver.com via QR image URL (`auth/totp-secret-leaked-via-external-qr-url`)

- **Repository Source Locations**: `routes/web.php:74`, `app/Http/Controllers/Auth/SecurityController.php:41`, `resources/views/auth/security.blade.php:63`, `app/Http/Controllers/Auth/SecurityController.php:69`
- **Lower-Trust Principal / Attacker Perspective**: Third-party QR service operator (api.qrserver.com), intermediary TLS/HTTP proxy logger, or local browser history observer inspecting requests triggered by GET /seguranca.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `GET /seguranca`, `POST /seguranca/totp/enable (code=<6-digit TOTP>)`
  - Steps: Authenticate as an active professor without TOTP enabled and request GET /seguranca. Extract the <img src> URL from the HTML response and URL-decode its data query parameter. Submit a valid 6-digit TOTP code generated from that secret to POST /seguranca/totp/enable and compare users.totp_secret against the secret leaked in the img src URL.
- **Conditions**: authentication_level: Requires an authenticated active user (professor or super_admin) who does not yet have TOTP enabled.; third_party_dependency: Browser fetches the rendered <img> src from https://api.qrserver.com, exposing the query string to that third party and any intermediary proxies/logs.
- **Actual Observed Result**: GET /seguranca returned HTTP 200 with <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=otpauth%3A%2F%2Ftotp%2FCorujinha%3Aqr-leak%40example.com%3Fsecret%3DI2DJ7D24ZEI3XUDQP2KLGWMOMKPG5LXS%26issuer%3DCorujinha%26algorithm%3DSHA1%26digits%3D6%26period%3D30">, matching session('totp_pending_secret') (I2DJ7D24ZEI3XUDQP2KLGWMOMKPG5LXS) and the value subsequently persisted to users.totp_secret on POST /seguranca/totp/enable.
- **Impact & Priority Rationale**:
  - *Impact*: Discloses the long-lived TOTP shared secret and user email to an external third-party service and URL logs, allowing anyone with access to those logs to forge valid 6-digit MFA codes.
  - *Likelihood*: Triggered automatically on every TOTP enrollment page load for every user.
  - *Overall*: **HIGH** (confidence: **high** — Directly verified in source and reproduced via HTTP kernel execution showing the exact session secret transmitted in the external QR URL and persisted unchanged to users.totp_secret.)
- **Smallest Source Fix**: Remove the external api.qrserver.com image URL and generate the TOTP QR code locally (e.g., server-side inline SVG using bacon/bacon-qr-code or client-side JavaScript QR rendering from $totpUri), and add a Content-Security-Policy restricting img-src.

File: `resources/views/auth/security.blade.php`
```php
<div class="mx-auto inline-block rounded-lg shadow-xs border border-royal-purple-200 bg-white p-2">
    {!! $totpQrSvg !!}
</div>
```

### 3. [HIGH] Public Reverb Channels Expose Professor PII and Classroom Access Codes Without Authentication (`reverb-realtime:public-admin-and-professor-channels-leak-pii-and-codes`)

- **Repository Source Locations**: `app/Http/Controllers/Admin/ProfessorController.php:65`, `app/Http/Controllers/Professor/ClassroomController.php:87`, `app/Events/ProfessorListUpdated.php:30`, `app/Events/ClassroomListUpdated.php:32`, `vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php:67`
- **Lower-Trust Principal / Attacker Perspective**: Unauthenticated external WebSocket client connecting from an arbitrary Origin (`https://untrusted-attacker.example`) using the public `VITE_REVERB_APP_KEY` embedded in the frontend bundle.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `{"event":"pusher:subscribe","data":{"channel":"admin.professores"}}`, `{"event":"pusher:subscribe","data":{"channel":"professor.1"}}`
  - Steps: Open a WebSocket connection to `/app/{VITE_REVERB_APP_KEY}` with any `Origin` header. Send `pusher:subscribe` frames for `admin.professores` and sequential `professor.{id}` channels without an `auth` signature. Wait for `professores.updated`, `salas.updated`, or `student.joined` frames emitted when admins invite/manage professors, professors activate accounts with CPF or create/update classrooms, or students join rooms.
- **Conditions**: system_configuration: Requires `BROADCAST_CONNECTION=reverb` (set in `.env.example:68`, workspace `.env`, and gitignored workspace `.env.production`) and the Reverb server process running (restarted in `.github/workflows/deploy.yml:84`).; network_routing: Requires client reachability to the Reverb WebSocket endpoint (`/app/{appKey}`), which is the same endpoint required for the application's real-time UI features (`resources/js/echo.js`).
- **Actual Observed Result**: In the `bwrap` sandbox test (`scratch/proof.txt`), `Pusher\Server::open()` accepted `Origin: https://untrusted-attacker.example` (`pusher:connection_established`), `EventHandler::subscribe()` returned `pusher_internal:subscription_succeeded` for `admin.professores` and `professor.1` with no `auth` token (while rejecting `private-admin.professores` with `4009`), and `EventDispatcher::dispatchSynchronously()` delivered the full `professores.updated` (including `email`, `cpf: 123.456.789-09`, `is_totp_enabled`), `salas.updated` (including `code: AVAL-DUMY`), and `student.joined` JSON frames to the unauthenticated socket.
- **Impact & Priority Rationale**:
  - *Impact*: Passive unauthenticated listeners capture Brazilian CPF numbers, emails, names, and TOTP status of professors as well as live classroom access codes that allow joining active assessments.
  - *Likelihood*: The channel names (`admin.professores` and sequential `professor.{id}`) are static or trivially enumerable, `allowed_origins` is `['*']`, and no subscription authentication is performed.
  - *Overall*: **HIGH** (confidence: **high** — Directly verified in event classes, `routes/channels.php`, `config/reverb.php`, Reverb's `ChannelBroker`/`Channel` source, and reproduced end-to-end against Reverb's protocol classes in the `bwrap` sandbox.)
- **Smallest Source Fix**: Convert `ProfessorListUpdated`, `ClassroomListUpdated`, and the professor-targeted channel in `StudentJoinedClassroom` to `PrivateChannel`, register strict authorization callbacks in `routes/channels.php` (`super_admin` for `private-admin.professores`, matching user ID for `private-professor.{id}`), update `resources/js/professors-monitor.js` and `resources/js/classrooms-list-monitor.js` to use `Echo.private(...)`, and restrict `config/reverb.php` `allowed_origins` to the configured app host.

File: `app/Events/ProfessorListUpdated.php`
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('admin.professores'),
    ];
}
```

File: `app/Events/ClassroomListUpdated.php`
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('professor.'.$this->classroom->user_id),
    ];
}
```

File: `routes/channels.php`
```php
Broadcast::channel('admin.professores', function ($user) {
    return $user->isActive() && $user->isSuperAdmin();
});

Broadcast::channel('professor.{id}', function ($user, int $id) {
    return $user->isActive() && ((int) $user->id === $id || $user->isSuperAdmin());
});
```

### 4. [HIGH] Mid-Exam Answer Key and Feedback Disclosure via Unauthenticated Attempt State in ClassroomPlayerController::showResult (`student-player-state/showresult-mid-exam-gabarito-disclosure`)

- **Repository Source Locations**: `routes/web.php:37`, `app/Http/Controllers/Student/ClassroomPlayerController.php:366`, `resources/views/estudante/resultado.blade.php:256`
- **Lower-Trust Principal / Attacker Perspective**: A student joins an active classroom via POST /sala/iniciar and navigates directly to GET /sala/{code}/resultado before submitting answers.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /sala/iniciar (code=AVAL-TEST, student_name=Aluno Teste 1)`, `GET /sala/AVAL-TEST/resultado`, `POST /sala/AVAL-TEST/atividade/responder (question_id=1, answer=B)`
  - Steps: Join classroom AVAL-TEST via POST /sala/iniciar to obtain a session with current_attempt_id in status 'in_progress'. Send GET /sala/AVAL-TEST/resultado using the same session cookie. Observe HTTP 200 with 'Gabarito Correto', expected highlight feedbacks, and option feedbacks, then submit the correct answer via POST /sala/AVAL-TEST/atividade/responder.
- **Conditions**: data_state: Requires an active classroom (is_active = true) and a started student session (current_attempt_id in session, status = 'in_progress').
- **Actual Observed Result**: GET /sala/AVAL-TEST/resultado returned HTTP 200 while status=in_progress and classroom_active=true, exposing Gabarito Correto, Expected command feedback secret, Expected clue feedback secret, and Devolutiva da alternativa B; subsequent POST /sala/AVAL-TEST/atividade/responder returned HTTP 200 {"success":true}.
- **Impact & Priority Rationale**:
  - *Impact*: Completely undermines assessment integrity by disclosing all correct options, feedbacks, and highlight expectations mid-exam while still allowing answer submission.
  - *Likelihood*: Any student in an active classroom can visit /sala/{code}/resultado directly in their browser with zero special tooling.
  - *Overall*: **HIGH** (confidence: **high** — Directly verified in source and reproduced end-to-end in the local Laravel HTTP kernel sandbox (proof.txt TEST 1).)
- **Smallest Source Fix**: Gate ClassroomPlayerController::showResult() so results and answer keys are only served when !$classroom->is_active or when the attempt has reached its terminal state ($attempt->status === 'reviewed', or $attempt->status === 'submitted' when !$classroom->collective_review_enabled), redirecting incomplete attempts back to the player or review step.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
if ($classroom->is_active) {
    if ($attempt->status === 'in_progress') {
        return redirect()->route('estudante.player', ['code' => $classroom->code]);
    }
    if ($classroom->collective_review_enabled && $attempt->status !== 'reviewed') {
        return redirect()->route('estudante.revisao', ['code' => $classroom->code]);
    }
}
```

### 5. [MEDIUM] Passkey login bypasses TOTP MFA without verifying the WebAuthn User Verified (UV) flag (`auth/passkey-assertion-missing-uv-flag-downgrades-mfa`)

- **Repository Source Locations**: `routes/web.php:50`, `app/Services/PasskeyService.php:190`, `app/Services/PasskeyService.php:243`, `app/Http/Controllers/Auth/AuthController.php:150`
- **Lower-Trust Principal / Attacker Perspective**: An attacker with access to a registered user's roaming security key (without its PIN) or unlocked authenticator client calls POST /login/passkey/options and submits a signed assertion with flags=0x01 (UP=1, UV=0) to POST /login/passkey.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `authenticatorData = sha256(rpId) || 0x01 (UP=1, UV=0) || pack('N', 2)`
  - Steps: Create an active professor user with totp_secret and totp_enabled_at set and an enrolled EC P-256 WebauthnCredential. POST /login/passkey/options and observe userVerification='preferred'. Sign an assertion with flags=chr(0x01) (UP=1, UV=0) and POST to /login/passkey.
- **Conditions**: data_state: Target user has an active WebauthnCredential registered and TOTP enabled.; user_interaction: Attacker has physical access to an enrolled authenticator or unlocked device capable of producing a UP=1, UV=0 assertion without PIN/biometric verification.
- **Actual Observed Result**: In bwrap sandbox (proof.txt TEST 1), POST /login/passkey/options returned HTTP 200 with userVerification=preferred, and POST /login/passkey with flags=0x01 returned HTTP 200 {"success":true,"redirect_url":"http://localhost/professor/salas"}, setting Auth::check()=true and Auth::id()=1 with totp_enabled=true and session_has_totp_auth_user_id=false.
- **Impact & Priority Rationale**:
  - *Impact*: Bypasses TOTP MFA on professor or super_admin accounts, granting a full authenticated session from presence-only assertions.
  - *Likelihood*: Requires access to an enrolled authenticator or client able to emit UP=1, UV=0 assertions, facilitated by the server advertising userVerification='preferred'.
  - *Overall*: **MEDIUM** (confidence: **high** — Verified directly in PasskeyService.php and AuthController.php and reproduced end-to-end against POST /login/passkey in the isolated bwrap test harness.)
- **Smallest Source Fix**: Require userVerification='required' in both PasskeyService::generateRegistrationOptions() and PasskeyService::generateAuthenticationOptions(), and enforce the WebAuthn UV bit (($flags & 0x04) === 0) inside PasskeyService::verifyAssertion() (or route to TOTP when UV is absent).

File: `app/Services/PasskeyService.php`
```php
'userVerification' => 'required',
...
$flags = ord($authDataRaw[32]);
if (($flags & 0x01) === 0) {
    throw new RuntimeException('Presença do usuário não confirmada pelo autenticador.');
}
if (($flags & 0x04) === 0) {
    throw new RuntimeException('Verificação do usuário (biometria/PIN) não confirmada pelo autenticador.');
}
```

### 6. [MEDIUM] Password reset does not invalidate active database sessions (`auth/password-reset-leaves-active-sessions-valid`)

- **Repository Source Locations**: `routes/web.php:62`, `app/Http/Controllers/Auth/ResetPasswordController.php:73`, `bootstrap/app.php:16`, `routes/web.php:113`
- **Lower-Trust Principal / Attacker Perspective**: An attacker holding a previously hijacked or persistent session cookie retains access to the victim's account even after the victim resets their password to recover the account.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /redefinir-senha (token, email, password, password_confirmation)`, `GET /professor/salas (with pre-reset session cookie)`
  - Steps: Log in as an active professor with SESSION_DRIVER=database to create an authenticated session row in the sessions table and verify GET /professor/salas returns HTTP 200. From a separate unauthenticated client, submit a valid password reset token and new 15+ char password to POST /redefinir-senha. Verify the user's password hash changed in the users table and check whether the prior session row still exists in sessions. Replay the pre-reset session cookie against GET /professor/salas.
- **Conditions**: system_configuration: Applies when session.driver is 'database' (the configured default in config/session.php and .env.example).; data_state: User has at least one active authenticated session prior to completing a password reset.
- **Actual Observed Result**: Before reset, session row qSY1isTFz0W0y3RbE93MoCKslMVKzKOCWncA6K8M (user_id=4) accessed GET /professor/salas with HTTP 200. After POST /redefinir-senha returned HTTP 302 and updated users.password, the pre-reset row remained in the sessions table (user_id=4, total_user_sessions=1) and GET /professor/salas with the pre-reset cookie still returned HTTP 200.
- **Impact & Priority Rationale**:
  - *Impact*: Pre-reset or compromised sessions remain valid indefinitely (within session lifetime) after password recovery, preventing effective account remediation.
  - *Likelihood*: Occurs deterministically whenever any user resets their password while an active session exists.
  - *Overall*: **MEDIUM** (confidence: **high** — Verified in ResetPasswordController::reset(), bootstrap/app.php, and proven via HTTP kernel execution with SQLite database sessions.)
- **Smallest Source Fix**: Invalidate all existing database sessions for the user during password reset and/or enable AuthenticateSession middleware in bootstrap/app.php.

File: `app/Http/Controllers/Auth/ResetPasswordController.php`
```php
function (User $user, string $password) {
    $user->forceFill([
        'password' => Hash::make($password),
        'remember_token' => Str::random(60),
    ])->save();

    if (config('session.driver') === 'database') {
        \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }
}
```

### 7. [MEDIUM] TOTP 6-digit login codes can be replayed across independent sessions within the validity window (`auth/totp-code-replay-within-validity-window`)

- **Repository Source Locations**: `routes/web.php:53`, `app/Http/Controllers/Auth/AuthController.php:104`, `app/Services/TotpService.php:35`
- **Lower-Trust Principal / Attacker Perspective**: An attacker who intercepts, shoulder-surfs, or phishes a victim's password and a single 6-digit TOTP code replays that same code in an independent session after the victim logs in.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /login (email, password)`, `POST /login/totp (code=<same 6-digit code in Session A and Session B>)`
  - Steps: Start two independent unauthenticated sessions (Session A and Session B) and submit valid email/password to POST /login for the same TOTP-enabled user. Compute a single 6-digit TOTP code for the current 30s time slice. Submit that code to POST /login/totp in Session A and verify GET /professor/salas succeeds. Immediately submit the identical 6-digit code to POST /login/totp in Session B within the same 30s slice and verify GET /professor/salas succeeds.
- **Conditions**: data_state: Target user has TOTP enabled and attacker already possesses valid password credentials (or pre-auth session) plus a recently submitted 6-digit TOTP code.; timing_dependency: Replay must occur within the 90-second (+/- 1 30s slice) acceptance window of the original code.
- **Actual Observed Result**: Both Session A and Session B submitted identical code 749458 in time slice 59815108, both received HTTP 302 redirects to /professor/salas, and both accessed GET /professor/salas with HTTP 200.
- **Impact & Priority Rationale**:
  - *Impact*: Defeats single-use property of TOTP codes, enabling an attacker to establish an independent authenticated session using an already-consumed OTP.
  - *Likelihood*: Requires the attacker to already know the user's password and obtain an active 6-digit TOTP code within its 90-second validity window.
  - *Overall*: **MEDIUM** (confidence: **high** — Confirmed in TotpService::verify() and reproduced in the HTTP kernel sandbox with two independent sessions authenticating on the same code.)
- **Smallest Source Fix**: Persist the last accepted TOTP time slice per user in the database (with row-level locking) or atomically under a cache lock keyed by user ID, reject any TOTP verification whose matching time slice is less than or equal to the last accepted slice, and call that replay-checked method from AuthController::verifyTotp().

File: `app/Services/TotpService.php`
```php
public function verifyOnce(int $userId, string $secret, string $code, int $window = 1): bool
{
    $code = trim($code);
    if (strlen($code) !== 6 || ! ctype_digit($code)) {
        return false;
    }

    $currentSlice = (int) floor(time() / 30);

    return \Illuminate\Support\Facades\Cache::lock("totp_used_slice_lock:{$userId}", 5)->block(3, function () use ($userId, $secret, $code, $window, $currentSlice): bool {
        $cacheKey = "totp_used_slice:{$userId}";
        $lastSlice = (int) \Illuminate\Support\Facades\Cache::get($cacheKey, 0);

        for ($i = -$window; $i <= $window; $i++) {
            $slice = $currentSlice + $i;
            if (hash_equals($this->calculateCode($secret, $slice), $code)) {
                if ($slice <= $lastSlice) {
                    return false;
                }

                \Illuminate\Support\Facades\Cache::put($cacheKey, $slice, now()->addMinutes(5));

                return true;
            }
        }

        return false;
    });
}
```

File: `app/Http/Controllers/Auth/AuthController.php`
```php
if (! $totpService->verifyOnce($user->id, (string) $user->totp_secret, (string) $request->input('code'))) {
    return back()->withErrors([
        'code' => 'Código de autenticação inválido ou expirado. Tente novamente.',
    ]);
}
```

### 8. [MEDIUM] Unvalidated reviews array in ClassroomPlayerController::finishReview executes one SELECT * FROM questions query per arbitrary key in a single request (`availability/finish-review-n-plus-one-question-queries`)

- **Repository Source Locations**: `routes/web.php:36`, `app/Http/Controllers/Student/ClassroomPlayerController.php:302`, `app/Http/Controllers/Student/ClassroomPlayerController.php:330`
- **Lower-Trust Principal / Attacker Perspective**: Unauthenticated student in an active classroom submitting a single POST /sala/{code}/revisao/concluir request containing hundreds of synthetic reviews keys.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /sala/C6-TEST/revisao/concluir with reviews[900001..900200][reviewed_answer]=A and reviews[900001..900200][review_reflection]=test`
  - Steps: Create an active classroom and student attempt in SQLite :memory: inside the bwrap sandbox. Enable DB::enableQueryLog() and dispatch one POST /sala/C6-TEST/revisao/concluir request with 200 synthetic reviews keys (900001..900200). Count total queries and SELECT queries targeting the questions table.
- **Conditions**: data_state: Requires an active classroom code and a student session created via POST /sala/iniciar.; environmental_dependency: Form-encoded payloads are subject to PHP max_input_vars (default 1000), whereas JSON request bodies bypass max_input_vars up to PHP post_max_size / Nginx client_max_body_size.
- **Actual Observed Result**: POST /sala/C6-TEST/revisao/concluir returned HTTP 302 and DB::enableQueryLog() recorded 207 total queries in 30.8 ms, including 201 SELECTs from questions (200 sequential 'select * from "questions" where "questions"."id" = ? limit 1' queries for bindings [900001]..[900200]).
- **Impact & Priority Rationale**:
  - *Impact*: Amplifies a single small student HTTP request into thousands of synchronous database queries, consuming PHP-FPM worker time and database connections.
  - *Likelihood*: Any unauthenticated student with an active classroom code can trigger arbitrary 1-to-K question query fan-out in a single HTTP request.
  - *Overall*: **MEDIUM** (confidence: **high** — Exactly 200 sequential SELECT * FROM questions queries were observed in DB::getQueryLog() from a single 200-key finishReview() request.)
- **Smallest Source Fix**: Validate reviews keys/size against the assessment questions (and reject/ignore when collective_review_enabled is false), and batch-load questions with whereIn instead of calling Question::find() inside the loop.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
$existingAnswers = $attempt->answers()->with('question')->get()->keyBy('question_id');
$validated = $request->validate([
    'reviews' => ['nullable', 'array', 'max:'.$existingAnswers->count()],
    'reviews.*.reviewed_answer' => ['nullable', 'in:A,B,C,D,E'],
    'reviews.*.review_reflection' => ['nullable', 'string', 'max:5000'],
]);
// Inside loop: $answerRecord = $existingAnswers->get((int) $questionId); if (! $answerRecord || ! $answerRecord->question) { continue; }
```

### 9. [MEDIUM] Unbounded markers array in saveAnswer amplifies CPU/DOM work in TextHighlightService::applyHighlights() and Blade marker rendering (`availability/unbounded-markers-highlight-amplification`)

- **Repository Source Locations**: `routes/web.php:33`, `app/Http/Controllers/Student/ClassroomPlayerController.php:152`, `app/Services/TextHighlightService.php:74`, `resources/views/professor/salas/tentativa-detalhes.blade.php:174`
- **Lower-Trust Principal / Attacker Perspective**: Unauthenticated student in an active classroom submitting oversized markers JSON arrays to POST /sala/{code}/atividade/responder.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `JSON array of 2,000 markers (117,891 bytes) + 10,000-byte reason via POST /sala/C4-TEST/atividade/responder`, `Bounded scaling benchmark with M=100 (5,741 bytes), M=2,000 (117,891 bytes), and M=5,000 (296,391 bytes) unique matching markers on 298,890-byte HTML (T=5,000 DOMText nodes)`
  - Steps: Call POST /sala/C4-TEST/atividade/responder with 2,000 marker objects and a 10,000-byte reason string inside the bwrap sandbox. Measure TextHighlightService::applyHighlights() wall time and memory delta across M=100, M=2,000, and M=5,000 unique markers on a 5,000-<p> HTML fixture.
- **Conditions**: data_state: Requires an active classroom code and a student attempt session; worst-case O(T * K) amplification scales with the number of DOMText nodes T and length L in the question's support_text.; environmental_dependency: Upper ceiling on a single HTTP POST body size is governed by external PHP post_max_size/memory_limit and Nginx client_max_body_size, though a 296 KB JSON payload (5,000 markers) is well below standard defaults.
- **Actual Observed Result**: saveAnswer accepted 2,000 markers + 10KB reason with HTTP 200. TextHighlightService::applyHighlights() scaled superlinearly on T=5,000 nodes: M=100 (5,741 B) took 100.91 ms; M=2,000 (117,891 B) took 1,164.73 ms (+2 MiB); M=5,000 (296,391 B) took 3,268.01 ms (+4 MiB).
- **Impact & Priority Rationale**:
  - *Impact*: Persists amplification payload that consumes multiple seconds of PHP worker CPU per render on student result views (3 calls per question) and inflates teacher attempt-detail rendering.
  - *Likelihood*: Any student with an active classroom code can submit thousands of markers in one request, though worst-case O(T * K) CPU amplification requires large/multi-node question support_text.
  - *Overall*: **MEDIUM** (confidence: **high** — Unbounded saveAnswer acceptance (HTTP 200) and superlinear scaling across M=100, 2,000, and 5,000 markers were directly measured in the sandbox.)
- **Smallest Source Fix**: Enforce strict max element count and field length validation on markers and reason in saveAnswer(), and replace the O(T * K) inner array_filter in TextHighlightService::applyHighlights() with a single sorted pointer pass.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
'reason' => ['required', 'string', 'max:5000'],
'markers' => ['nullable', 'array', 'max:100'],
'markers.*.text' => ['required', 'string', 'max:1000'],
```

### 10. [MEDIUM] Unvalidated nested markers array in ClassroomPlayerController::saveAnswer causes persistent HTTP 500 on teacher attempt details and student result views (`chain:saveAnswer-unvalidated-markers-to-teacher-tentativa-detalhes-500`)

- **Repository Source Locations**: `routes/web.php:33`, `app/Http/Controllers/Student/ClassroomPlayerController.php:152`, `app/Http/Controllers/Student/ClassroomPlayerController.php:188`, `resources/views/professor/salas/tentativa-detalhes.blade.php:176`, `app/Services/TextHighlightService.php:75`
- **Lower-Trust Principal / Attacker Perspective**: Unauthenticated student who joins an active classroom via POST /sala/iniciar and submits JSON to POST /sala/{code}/atividade/responder.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `{"question_id":1,"answer":"B","reason":"Minha justificativa","markers":[{"kind":"command","text":["nested"]},{"kind":"evidence","text":"ok"}],"time_spent":5000}`
  - Steps: Create a professor, question, assessment, active classroom, and student attempt in SQLite :memory: inside the bwrap sandbox. Dispatch JSON POST /sala/C1-TEST/atividade/responder with session current_attempt_id and markers: [['kind' => 'command', 'text' => ['nested']], ['kind' => 'evidence', 'text' => 'ok']]. Authenticate as the owning professor and dispatch GET /professor/salas/{classroom}/tentativas/{attempt}. Dispatch GET /sala/C1-TEST/resultado with the student attempt session and also invoke TextHighlightService::applyHighlights() directly.
- **Conditions**: data_state: Requires an active classroom code and a student session created via POST /sala/iniciar.; user_interaction: Professor triggers the teacher-side HTTP 500 when opening the affected student's attempt details page (professor.salas.tentativa).
- **Actual Observed Result**: POST /sala/C1-TEST/atividade/responder returned HTTP 200 {"success":true} and persisted [{"kind":"command","text":["nested"]},{"kind":"evidence","text":"ok"}] in student_answers.initial_markers. GET /professor/salas/{id}/tentativas/{id} returned HTTP 500 with Illuminate\View\ViewException wrapping TypeError: htmlspecialchars(): Argument #1 ($string) must be of type string, array given at helpers.php:138. GET /sala/C1-TEST/resultado returned HTTP 500 with Illuminate\View\ViewException wrapping ErrorException: Array to string conversion at TextHighlightService.php:75.
- **Impact & Priority Rationale**:
  - *Impact*: Persistently breaks the professor's attempt detail view (HTTP 500) for that student attempt and the student's result page, while leaving other classrooms and attempts unaffected.
  - *Likelihood*: Any unauthenticated student who enters an active classroom code can submit the malformed JSON payload in a single request.
  - *Overall*: **MEDIUM** (confidence: **high** — Both HTTP 500 responses and exact PHP exceptions were reproduced end-to-end in the sandboxed Laravel HTTP kernel.)
- **Smallest Source Fix**: Add strict item-level validation for markers in ClassroomPlayerController::saveAnswer() enforcing scalar string types and allowed fields, and add defensive is_array/is_string checks in TextHighlightService::applyHighlights() and Blade marker loops.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
'markers' => ['nullable', 'array', 'max:200'],
'markers.*' => ['required', 'array'],
'markers.*.kind' => ['required', 'string', 'in:command,evidence,clue'],
'markers.*.target' => ['nullable', 'string', 'in:title,support,source,prompt'],
'markers.*.text' => ['required', 'string', 'max:2000'],
'markers.*.start' => ['nullable', 'integer', 'min:0'],
'markers.*.end' => ['nullable', 'integer', 'min:0'],
```

File: `app/Services/TextHighlightService.php`
```php
if (! is_array($m) || ! is_string($m['text'] ?? null)) {
    continue;
}
$snippet = trim($m['text']);
```

### 11. [MEDIUM] Unauthenticated Sequential IDOR on Private and Draft Question Support Images via /midia/questoes/{question} (`professor-admin-isolation:midia-questao-unauthenticated-idor`)

- **Repository Source Locations**: `routes/web.php:25`, `app/Http/Controllers/Professor/QuestionController.php:193`
- **Lower-Trust Principal / Attacker Perspective**: An unauthenticated remote user enumerates integer IDs on GET /midia/questoes/{id}.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `GET /midia/questoes/5`
  - Steps: Create a draft Question owned by Teacher B not attached to any Assessment or Classroom, backed by dummy image file questions/images/dummy.jpg. Send an unauthenticated GET /midia/questoes/{id} request without login or student classroom cookies.
- **Conditions**: data_state: Requires at least one Question record with a non-null support_image_path pointing to an existing file on the public disk.
- **Actual Observed Result**: Unauthenticated GET /midia/questoes/5 returned HTTP 200 with Content-Type: image/jpeg and streamed the exact 48-byte dummy image from Teacher B's draft question.
- **Impact & Priority Rationale**:
  - *Impact*: Discloses private and unpublished draft teacher question images across tenants and outside active exams.
  - *Likelihood*: Public endpoint accepting predictable sequential integer Question IDs with zero authentication.
  - *Overall*: **MEDIUM** (confidence: **high** — Directly verified in source and reproduced unauthenticated in proof.txt TEST 7.)
- **Smallest Source Fix**: Require either an authenticated owning professor/super-admin or a valid student session whose active classroom assessment contains the requested question, and move private question uploads off the public symlinked disk.

File: `app/Http/Controllers/Professor/QuestionController.php`
```php
$user = Auth::user();
$allowedAsTeacher = $user && ($question->user_id === $user->id || $user->isSuperAdmin());
$attemptId = session('current_attempt_id');
$allowedAsStudent = $attemptId && \App\Models\StudentAttempt::where('id', $attemptId)
    ->whereHas('classroom', fn ($q) => $q->where('is_active', true)->whereHas('assessment.questions', fn ($qq) => $qq->where('questions.id', $question->id)))
    ->exists();
if (! $allowedAsTeacher && ! $allowedAsStudent) {
    abort(403);
}
```

### 12. [MEDIUM] Public Classroom Channel `classroom.{id}` Leaks Question Content, Room Access Codes, and Peer Student Telemetry (`reverb-realtime:public-classroom-channel-leaks-questions-and-student-telemetry`)

- **Repository Source Locations**: `app/Http/Controllers/Professor/QuestionController.php:182`, `app/Http/Controllers/Professor/ClassroomController.php:329`, `app/Http/Controllers/Student/ClassroomPlayerController.php:66`, `app/Events/QuestionUpdated.php:34`, `vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php:67`
- **Lower-Trust Principal / Attacker Perspective**: Either (1) an unauthenticated external WebSocket client enumerating `classroom.1`, `classroom.2`, ... or (2) an unauthenticated student already connected to `classroom.{id}` via `classroom-player.js` inspecting WebSocket frames.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `{"event":"pusher:subscribe","data":{"channel":"classroom.1"}}`
  - Steps: Connect to Reverb WebSocket `/app/{VITE_REVERB_APP_KEY}` (or open DevTools Network WS inspector as an active student in a classroom). Send `pusher:subscribe` for `classroom.1` (and sequential IDs) with no `auth` parameter. Observe incoming `student.joined`, `student.updated`, `question.updated`, and `classroom.closed` frames.
- **Conditions**: system_configuration: Requires `BROADCAST_CONNECTION=reverb` and Reverb WebSocket server running (active in `.env.example:68`, workspace `.env.production`, and `.github/workflows/deploy.yml:84`).; network_routing: Requires reachability of Reverb's `/app/{appKey}` WebSocket endpoint; peer student exposure requires only the normal student assessment connection.
- **Actual Observed Result**: In the `bwrap` sandbox test (`scratch/proof.txt`), subscribing without `auth` to `classroom.1` returned `pusher_internal:subscription_succeeded`, and `EventDispatcher::dispatchSynchronously()` delivered `student.joined` (`student_name: Estudante Dummy Silva`, `score: 1`), `question.updated` (full `support_text`, `prompt`, and all 5 `options`), `classroom.closed` (`code: AVAL-DUMY`, `redirect_url: /sala/AVAL-DUMY/resultado`), and `student.updated` (`status`, `score`) to the unauthenticated client.
- **Impact & Priority Rationale**:
  - *Impact*: Exposes peer student names and live scores to classmates and leaks full question content and classroom access codes on update/closure to anonymous subscribers (note: `toPlayerPayload()` omits `correct_option` and feedback fields).
  - *Likelihood*: Classroom IDs are sequential integers and every active student browser already receives peer telemetry on the open WebSocket without any extra action.
  - *Overall*: **MEDIUM** (confidence: **high** — Verified across all four event classes, `Question::toPlayerPayload()`, JS subscribers, and reproduced locally in `bwrap` against Reverb's server and channel classes.)
- **Smallest Source Fix**: Split teacher monitoring and student broadcast channels: move `StudentJoinedClassroom` and `StudentAttemptUpdated` onto a dedicated `PrivateChannel('classroom.monitor.'.$classroomId)` authorized in `routes/channels.php` only for the owning professor or `super_admin`. For student-facing `QuestionUpdated` and `ClassroomClosed` events, replace predictable `classroom.{id}` names with a per-classroom unguessable broadcast channel identifier or authorize student subscriptions via a session-backed broadcasting auth endpoint.

File: `app/Events/StudentAttemptUpdated.php`
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('classroom.monitor.'.$this->attempt->classroom_id),
    ];
}
```

File: `app/Events/StudentJoinedClassroom.php`
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('classroom.monitor.'.$this->attempt->classroom_id),
        new PrivateChannel('professor.'.$this->attempt->classroom->user_id),
    ];
}
```

### 13. [MEDIUM] Cross-Assessment and Cross-Tenant Question Injection via Unscoped question_id in ClassroomPlayerController::saveAnswer (`student-player-state/cross-assessment-question-injection-score-inflation`)

- **Repository Source Locations**: `routes/web.php:33`, `app/Http/Controllers/Student/ClassroomPlayerController.php:149`, `app/Http/Controllers/Student/ClassroomPlayerController.php:180`, `app/Models/StudentAttempt.php:47`
- **Lower-Trust Principal / Attacker Perspective**: An authenticated student session in Teacher A's 2-question classroom submits POST /sala/{code}/atividade/responder with question_id values belonging to Teacher B.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /sala/AVAL-TEST/atividade/responder {"question_id":3,"answer":"D","reason":"Answer reason","markers":[],"time_spent":1000}`, `POST /sala/AVAL-TEST/atividade/responder {"question_id":4,"answer":"E","reason":"Answer reason","markers":[],"time_spent":1000}`
  - Steps: Join Teacher A's 2-question classroom AVAL-TEST via POST /sala/iniciar. Submit correct answers for the 2 assessment questions plus 2 foreign questions belonging to Teacher B (qid=3, qid=4) via POST /sala/AVAL-TEST/atividade/responder. Inspect $attempt->score, $attempt->earnedPoints(), and AssessmentController::statistics($assessmentA).
- **Conditions**: data_state: Requires an active classroom session and at least one other Question record in the database outside $classroom->assessment.
- **Actual Observed Result**: All four saveAnswer requests returned HTTP 200 {"success":true}. Attempt2 recorded score=4/2 and earnedPoints=6/4 (max expected 2 and 4.0), and AssessmentController::statistics() reported accuracy_rate=125% for Classroom A.
- **Impact & Priority Rationale**:
  - *Impact*: Allows cross-tenant question references, unbounded score/points inflation beyond assessment maximums, and corruption of professor analytics.
  - *Likelihood*: Requires tampering with JSON payload question_id and guessing sequential integer IDs and options A-E.
  - *Overall*: **MEDIUM** (confidence: **high** — Directly verified in source and reproduced end-to-end in the local sandbox (proof.txt TEST 2).)
- **Smallest Source Fix**: Scope question_id validation in saveAnswer() to questions belonging to $classroom->assessment_id via assessment_question, and filter answers in StudentAttempt::recalculateScore(), StudentAttempt::earnedPoints(), and AssessmentController::statistics() to the assessment's question IDs.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
$question = $classroom->assessment->questions()->where('questions.id', $validated['question_id'])->first();
if (! $question) {
    return response()->json(['error' => 'Questão não pertence a esta avaliação.'], 422);
}
```

### 14. [MEDIUM] Collective Review Disablement Bypass and Sequential Replay Limit Bypass in finishReview (`student-player-state/finishreview-disabled-and-replay-limit-bypass`)

- **Repository Source Locations**: `routes/web.php:36`, `app/Http/Controllers/Student/ClassroomPlayerController.php:288`, `app/Http/Controllers/Student/ClassroomPlayerController.php:321`
- **Lower-Trust Principal / Attacker Perspective**: A student in an active classroom POSTs directly to /sala/{code}/revisao/concluir either when collective review is disabled or as multiple partial requests with one question per request.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /sala/AVAL-NORE/revisao/concluir reviews[1][reviewed_answer]=B&reviews[2][reviewed_answer]=C`, `POST /sala/AVAL-TEST/revisao/concluir reviews[1][reviewed_answer]=B (followed by) POST /sala/AVAL-TEST/revisao/concluir reviews[2][reviewed_answer]=C`
  - Steps: In classroom AVAL-NORE (collective_review_enabled=false), answer Q1 and Q2 wrongly, submit attempt, then POST both corrections to /sala/AVAL-NORE/revisao/concluir. In classroom AVAL-TEST (collective_review_enabled=true, max_review_changes=1), answer Q1 and Q2 wrongly, submit attempt, then send two sequential POSTs to /sala/AVAL-TEST/revisao/concluir changing Q1 in the first and Q2 in the second.
- **Conditions**: data_state: Requires an active classroom session; replay bypass requires an assessment with more questions than max_review_changes.
- **Actual Observed Result**: On AVAL-NORE, GET /revisao redirected to resultado, but POST /revisao/concluir returned 302 to resultado with attempt_status=reviewed and score=2. On AVAL-TEST (max=1), Replay 1 set score=1 and Replay 2 set score=2 with total_changed_in_db=2.
- **Impact & Priority Rationale**:
  - *Impact*: Enables students to override disabled collective review and exceed the teacher-configured max_review_changes limit across any number of questions.
  - *Likelihood*: Requires only standard student session cookies and sequential form POSTs while the classroom is active.
  - *Overall*: **MEDIUM** (confidence: **high** — Directly verified in source and reproduced for both bypass vectors in proof.txt TEST 4.)
- **Smallest Source Fix**: Abort or redirect in finishReview() if !$classroom->collective_review_enabled or $attempt->status !== 'submitted', and calculate total changed questions across all existing answers merged with the request payload.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
if (! $classroom->collective_review_enabled) {
    return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
}
if ($attempt->status === 'reviewed') {
    return redirect()->route('estudante.resultado', ['code' => $classroom->code])
        ->with('info', 'A revisão coletiva já foi concluída.');
}
```

### 15. [MEDIUM] Missing Input Validation in finishReview and Incomplete Nested Validation in saveAnswer Causing Unhandled 500s and Invalid Answer/Marker Storage (`student-player-state/finishreview-saveanswer-missing-input-validation`)

- **Repository Source Locations**: `routes/web.php:33`, `app/Http/Controllers/Student/ClassroomPlayerController.php:145`, `resources/views/estudante/resultado.blade.php:121`
- **Lower-Trust Principal / Attacker Perspective**: A student session sends malformed JSON or form payloads to /sala/{code}/atividade/responder or /sala/{code}/revisao/concluir.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /sala/AVAL-TEST/revisao/concluir (reviews='bad-string')`, `POST /sala/AVAL-TEST/revisao/concluir (reviews[1][reviewed_answer]=['B'])`, `POST /sala/AVAL-TEST/revisao/concluir (reviews[1][reviewed_answer]='Z')`, `POST /sala/AVAL-TEST/atividade/responder {"question_id":1,"answer":["A"],"reason":"x"}`, `POST /sala/AVAL-TEST/atividade/responder {"question_id":1,"answer":"A","reason":"x","markers":[{"kind":"command","text":["nested"]}]}`
  - Steps: Send malformed reviews='bad-string', reviewed_answer=['B'], and reviewed_answer='Z' to POST /sala/AVAL-TEST/revisao/concluir. Send answer=['A'] and markers=[{'kind':'command','text':['nested']}] to POST /sala/AVAL-TEST/atividade/responder, then GET /sala/AVAL-TEST/resultado.
- **Conditions**: data_state: Requires an active classroom and student session; triggering the persisted ViewException requires visiting resultado after saving nested array marker text.
- **Actual Observed Result**: finishReview returned HTTP 500 ErrorException on reviews='bad-string', HTTP 500 TypeError on reviewed_answer=['B'], and HTTP 302 storing reviewed_answer='Z' in DB (reviews[qid]='not-an-array' returned 302 due to isset/??). saveAnswer returned HTTP 500 TypeError on answer=['A'], HTTP 200 on markers=['not-an-array'], and HTTP 200 on markers[0][text]=['nested'] which subsequently caused HTTP 500 ViewException (Array to string conversion) on GET /sala/AVAL-TEST/resultado.
- **Impact & Priority Rationale**:
  - *Impact*: Causes unhandled HTTP 500 errors on malformed inputs, persists invalid non-option values (such as 'Z') into student_answers.reviewed_answer, and enables persistent HTTP 500 ViewException errors on student result rendering via nested marker arrays.
  - *Likelihood*: Requires crafted form or JSON payloads from a student session in an active classroom.
  - *Overall*: **MEDIUM** (confidence: **high** — Directly verified in ClassroomPlayerController.php and resultado.blade.php, and reproduced with exact exception classes in proof.txt TEST 5.)
- **Smallest Source Fix**: Add strict $request->validate() rules in finishReview() requiring reviews to be an array of arrays with reviewed_answer in A..E and string review_reflection; in saveAnswer(), guard strtoupper() with is_string() and validate answer as string plus nested markers.* schema rules.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
// Inside saveAnswer():
if (is_string($request->input('answer'))) {
    $request->merge(['answer' => trim(strtoupper($request->input('answer')))]);
}

$validated = $request->validate([
    'question_id' => ['required', 'exists:questions,id'],
    'answer' => ['required', 'string', 'in:A,B,C,D,E'],
    'reason' => ['required', 'string', 'max:5000'],
    'markers' => ['nullable', 'array', 'max:200'],
    'markers.*' => ['required', 'array'],
    'markers.*.kind' => ['required', 'string', 'in:command,evidence,clue'],
    'markers.*.target' => ['nullable', 'string', 'in:title,support,source,prompt'],
    'markers.*.text' => ['required', 'string', 'max:2000'],
    'markers.*.start' => ['nullable', 'integer', 'min:0'],
    'markers.*.end' => ['nullable', 'integer', 'min:0'],
    'time_spent' => ['nullable', 'integer', 'min:0'],
]);
```

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
// Inside finishReview():
$validated = $request->validate([
    'reviews' => ['nullable', 'array'],
    'reviews.*' => ['required', 'array'],
    'reviews.*.reviewed_answer' => ['nullable', 'string', 'in:a,b,c,d,e,A,B,C,D,E'],
    'reviews.*.review_reflection' => ['nullable', 'string', 'max:5000'],
]);
$reviews = $validated['reviews'] ?? [];
```

### 16. [MEDIUM] Post-Submission and Post-Review Answer Tampering via Missing Attempt Status Guard in saveAnswer (`student-player-state/saveanswer-post-submission-state-bypass`)

- **Repository Source Locations**: `routes/web.php:33`, `app/Http/Controllers/Student/ClassroomPlayerController.php:130`, `app/Http/Controllers/Student/ClassroomPlayerController.php:180`
- **Lower-Trust Principal / Attacker Perspective**: A student submits their attempt (or completes collective review) and replays POST /sala/{code}/atividade/responder while the classroom is still open.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `POST /sala/AVAL-TEST/atividade/concluir`, `POST /sala/AVAL-TEST/atividade/responder {"question_id":1,"answer":"B","reason":"Overwritten after submit","markers":[]}`
  - Steps: Join classroom AVAL-TEST, answer Q1 and Q2 wrongly ('A'), and call POST /sala/AVAL-TEST/atividade/concluir (status='submitted'). Replay POST /sala/AVAL-TEST/atividade/responder for Q1 with answer='B', then call finishReview changing Q2 to 'C'. In a separate attempt, call submitAttempt and finishReview (status='reviewed', score=0), then replay POST /sala/AVAL-TEST/atividade/responder for Q1 with answer='B'.
- **Conditions**: data_state: Requires a classroom that remains active (is_active = true) after the student calls submitAttempt or finishReview.
- **Actual Observed Result**: While status='submitted', saveAnswer returned HTTP 200, updated q1 initial_answer='B' and is_correct_final=true (score=1), enabling finishReview to change Q2 as well for score=2 despite max_review_changes=1. While status='reviewed', saveAnswer returned HTTP 200, updated initial_answer='B', is_correct_final=true, and score=1 while reviewed_answer remained 'A'.
- **Impact & Priority Rationale**:
  - *Impact*: Subverts the two-stage individual-vs-collective evaluation model, bypasses max_review_changes, and leaves reviewed_answer inconsistent with score and is_correct_final.
  - *Likelihood*: Any student can send a POST to /sala/{code}/atividade/responder with their existing session while the classroom is open.
  - *Overall*: **MEDIUM** (confidence: **high** — Directly verified in source and reproduced for both 'submitted' and 'reviewed' states in proof.txt TEST 3.)
- **Smallest Source Fix**: Enforce $attempt->status === 'in_progress' in saveAnswer() (and guard submitAttempt() against already-submitted/reviewed attempts), returning HTTP 422 when the attempt has already been submitted.

File: `app/Http/Controllers/Student/ClassroomPlayerController.php`
```php
if ($attempt->status !== 'in_progress') {
    return response()->json([
        'error' => 'As respostas iniciais já foram enviadas e não podem mais ser alteradas.',
    ], 422);
}
```

### 17. [LOW] Passkey origin check ignores scheme and port via parse_url(PHP_URL_HOST) and derives rpId from request Host header (`auth/passkey-host-header-and-origin-scheme-bypass`)

- **Repository Source Locations**: `routes/web.php:49`, `app/Services/PasskeyService.php:45`, `app/Services/PasskeyService.php:316`
- **Lower-Trust Principal / Attacker Perspective**: An attacker supplies a WebAuthn assertion with clientDataJSON.origin downgraded to http://<host>:9999 (or from an alternate port/service on the same host) against an HTTPS passkey login endpoint, or supplies a spoofed Host header if upstream Nginx does not filter Host.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `clientDataJSON.origin = "http://localhost:9999" over HTTPS`, `clientDataJSON.origin = "http://corujinha.bissoli.tech:9999" over HTTPS with APP_URL=https://corujinha.bissoli.tech`, `HTTP Host: evil.example with clientDataJSON.origin = "http://evil.example:8888"`
  - Steps: Set APP_URL=https://corujinha.bissoli.tech and send HTTPS POST /login/passkey/options and POST /login/passkey with clientDataJSON.origin='http://corujinha.bissoli.tech:9999' (and similarly https://localhost with origin='http://localhost:9999'). Send POST /login/passkey/options and POST /login/passkey with Host: evil.example and signed rpIdHash=sha256('evil.example').
- **Conditions**: system_configuration: Scheme/port origin bypass is unconditional in PasskeyService::verifyClientData() regardless of proxy configuration; exploiting Host-header rpId injection with an arbitrary domain depends on whether upstream CloudPanel Nginx forwards unrecognized Host headers to PHP-FPM (outside repository).; network_routing: Browser WebAuthn APIs only generate assertions for secure contexts (https:// on same host/alternate port or http://localhost:<port>).
- **Actual Observed Result**: In bwrap sandbox (proof.txt TEST 2): 2A (HTTPS localhost, origin='http://localhost:9999') returned HTTP 200; 2C (HTTPS corujinha.bissoli.tech, origin='http://corujinha.bissoli.tech:9999') returned HTTP 200; 2B (Host: evil.example with APP_URL=https://corujinha.bissoli.tech) returned rpId='evil.example' in options and HTTP 200 on verify.
- **Impact & Priority Rationale**:
  - *Impact*: Weakens WebAuthn origin and Relying Party binding across schemes, ports, and (if Nginx allows arbitrary Host) domains.
  - *Likelihood*: Exploiting alternate scheme/port requires another compromised service/port on the same hostname or localhost, and Host-header rpId control requires permissive Nginx vhost routing.
  - *Overall*: **LOW** (confidence: **high** — Scheme/port bypass in verifyClientData() and $request->getHost() usage in rpId() are directly confirmed in PasskeyService.php and executed in proof.txt TEST 2.)
- **Smallest Source Fix**: Pin RP ID and allowed origin(s) from configuration (e.g., parse_url(config('app.url'), PHP_URL_HOST) and rtrim(config('app.url'), '/')), validate exact scheme+host+port origin equality in verifyClientData(), and enable TrustHosts middleware.

File: `app/Services/PasskeyService.php`
```php
$expectedOrigin = rtrim((string) config('app.url'), '/');
if (! hash_equals($expectedOrigin, rtrim($origin, '/'))) {
    throw new RuntimeException('Origem da requisição WebAuthn não autorizada.');
}
```

### 18. [LOW] Passkey clone-detection check is bypassed when an assertion reports sign_count=0 against a non-zero stored counter (`auth/passkey-sign-count-zero-regression-bypass`)

- **Repository Source Locations**: `routes/web.php:50`, `app/Http/Controllers/Auth/AuthController.php:147`, `app/Services/PasskeyService.php:263`
- **Lower-Trust Principal / Attacker Perspective**: An attacker possessing a cloned passkey private key for a credential with stored sign_count > 0 sets the 4-byte signCount field in authenticatorData to 0 (pack('N', 0)) and submits a signed assertion to POST /login/passkey.
- **Target-Native Bounded Reproduction**:
  - Payloads / Inputs: `authenticatorData = sha256(rpId) || 0x05 || 0x00000000 (sign_count=0 against stored sign_count=5)`
  - Steps: Set a WebauthnCredential row's sign_count to 5. Control test: submit a valid signed assertion with sign_count=4 to POST /login/passkey (expect HTTP 422 clone error). Bypass test: submit a valid signed assertion with sign_count=0 to POST /login/passkey.
- **Conditions**: data_state: The stored WebauthnCredential must already have sign_count > 0.; authentication_level: Attacker must possess a cloned private key capable of producing a valid signature over authenticatorData with sign_count=0.
- **Actual Observed Result**: In bwrap sandbox (proof.txt TEST 3): sign_count=4 returned HTTP 422 {"error":"Contador de uso da Passkey inválido (possível credencial clonada)."}, whereas sign_count=0 returned HTTP 200 {"success":true,"redirect_url":"http://localhost/professor/salas"} with Auth::check()=true and stored_sign_count_after=5.
- **Impact & Priority Rationale**:
  - *Impact*: Evades the application's anti-cloning detection control so cloned credential usage proceeds silently.
  - *Likelihood*: Requires prior compromise/cloning of the credential's private key to sign a valid assertion with sign_count=0.
  - *Overall*: **LOW** (confidence: **high** — Exact condition verified in PasskeyService.php:263 and demonstrated with control (sign_count=4 -> 422) vs bypass (sign_count=0 -> 200) in proof.txt TEST 3.)
- **Smallest Source Fix**: Trigger the signature counter check whenever either the stored counter or the incoming counter is non-zero (`$incomingSignCount > 0 || $credential->sign_count > 0`).

File: `app/Services/PasskeyService.php`
```php
if (($incomingSignCount > 0 || $credential->sign_count > 0) && $incomingSignCount <= $credential->sign_count) {
    throw new RuntimeException('Contador de uso da Passkey inválido (possível credencial clonada).');
}
```

## 5. NEEDS VALIDATION (Unresolved Leads — No Severity Assigned)

The following 7 source-grounded leads could not be confirmed or refuted locally due to missing deployment infrastructure or external runtime dependencies. They are **not** confirmed vulnerabilities and carry no severity rating until checked via the listed validation steps.

| # | Fingerprint | Title | Repository Trace | Exact Blocker(s) | Bounded Local Next Step | Owner-Observed Deployment Check |
|---:|---|---|---|---|---|---|
| 1 | `auth/password-reset-host-header-poisoning` | Password reset notification generates reset URL from incoming Host header without TrustHosts or forced root URL | `routes/web.php:60` -> `app/Http/Controllers/Auth/ForgotPasswordController.php:33` -> `app/Notifications/ResetPasswordNotification.php:35` | Live CloudPanel Nginx virtual-host configuration (server_name matching, presence of a catch-all default_server block, and fastcgi_param HTTP_HOST / SERVER_NAME handling) is outside the repository and unobserved. | Already verified in bwrap sandbox (proof.txt TEST 4): POST /esqueci-minha-senha with Host: evil.example produced actionUrl=http://evil.example/redefinir-senha/<token>?email=prof.totp%40example.com. | Inspect the live CloudPanel Nginx vhost for corujinha.bissoli.tech (and any default_server block on ports 80/443) or send a single bounded HEAD/GET request with a benign Host header to confirm whether Nginx rejects unknown Host headers before PHP-FPM. |
| 2 | `auth/totp-login-unlimited-brute-force-bypass` | POST /login/totp lacks application-level rate limiting or attempt lockout across failed TOTP challenges | `routes/web.php:53` -> `app/Http/Controllers/Auth/AuthController.php:90` -> `app/Http/Controllers/Auth/AuthController.php:104` | CloudPanel Nginx vhost and any edge/WAF/Fail2ban rate-limiting rules (e.g., limit_req_zone / limit_req on /login/totp) for corujinha.bissoli.tech are outside the repository and cannot be verified from source. | Already verified in bwrap sandbox (proof.txt): 20 consecutive wrong TOTP submissions to POST /login/totp all returned HTTP 302 without 429 or clearing totp_auth_user_id, and attempt 21 with the valid code redirected to /professor/salas (HTTP 200). | Inspect the CloudPanel Nginx vhost configuration and WAF/Fail2ban rules for corujinha.bissoli.tech to check whether POST /login/totp is enforced with strict per-IP/per-session rate limiting (e.g., 5 requests/minute), or send a bounded owner-authorized burst of >15 invalid POST /login/totp requests against a staging/production instance sharing the same proxy config to observe whether HTTP 429 is returned. |
| 3 | `availability/unbounded-attempts-eager-load-n-plus-one` | Unbounded eager-loading and N+1 lazy-loading across ClassroomController::show, devolutiva, and AssessmentController::statistics | `routes/web.php:29` -> `app/Http/Controllers/Professor/ClassroomController.php:102` -> `app/Http/Controllers/Professor/ClassroomController.php:218` -> `app/Http/Controllers/Professor/AssessmentController.php:186` | Turning linear O(N) attempt accumulation into teacher-route OOM or request timeout requires sustained POST /sala/iniciar (and saveAnswer) request volume whose feasibility depends on CloudPanel/Nginx reverse-proxy rate limiting or WAF controls outside the repository.; Exact failure thresholds (number of attempts/answers required to exhaust memory or timeout) depend on deployed PHP memory_limit, max_execution_time, and MariaDB query/latency configuration outside the repository. | None (blocker is purely external to repository source). | Owner should inspect CloudPanel Nginx configuration for rate limits on /sala/iniciar and /sala/*/atividade/responder, and check production PHP memory_limit, max_execution_time, and PHP-FPM request_terminate_timeout against expected maximum class/assessment attempt counts. |
| 4 | `chain:concurrent-saveAnswer-duplicate-student-answers-score-inflation` | Missing unique constraint on student_answers and non-atomic updateOrCreate in saveAnswer allow duplicate answer rows to inflate scores if concurrent requests overlap | `routes/web.php:33` -> `app/Http/Controllers/Student/ClassroomPlayerController.php:180` -> `database/migrations/2026_11_10_152104_create_student_answers_table.php:17` -> `app/Models/StudentAttempt.php:49` -> `app/Models/StudentAttempt.php:77` -> `app/Http/Controllers/Professor/AssessmentController.php:197` | Deployed PHP-FPM worker concurrency (pm.max_children > 1) and upstream CloudPanel Nginx request concurrency/rate-limiting rules governing whether simultaneous POST /sala/{code}/atividade/responder requests overlap in production are outside the repository.; Production MariaDB engine/transaction_isolation and locking behavior on concurrent StudentAnswer::updateOrCreate() calls (as opposed to local SQLite) are outside the repository. | Run a bounded two-process HTTP concurrency test against a disposable local MariaDB/MySQL instance (or file-backed database with parallel workers) issuing two simultaneous POST /sala/{code}/atividade/responder requests for the same (attempt_id, question_id) and assert whether two student_answers rows are inserted. | Owner should inspect production PHP-FPM pm.max_children, MariaDB transaction_isolation and student_answers indexes, and CloudPanel Nginx concurrency/rate-limiting rules, and query student_answers for duplicate (student_attempt_id, question_id) rows. |
| 5 | `deploy.env-example-reverb-secret-reuse` | Committed `.env.example` Reverb Credentials Match Local `.env.production` and Enable Pusher HTTP API Request Forgery If Reused and Exposed | `.env.example:70` -> `.github/workflows/deploy.yml:60` -> `config/reverb.php:76` -> `vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php:115` | `.env.production.encrypted` cannot be decrypted without the repository owner's `LARAVEL_ENV_ENCRYPTION_KEY` secret, so whether the deployed `.env` on the VPS matches the workspace's gitignored `.env.production` (which `hash_equals` confirmed matches `.env.example`) is unverified.; CloudPanel Nginx vhost and host firewall rules are outside the repository, so it is unknown whether Reverb's HTTP API paths (`/apps/{appId}/events`, `/apps/{appId}/channels`) or `REVERB_SERVER_PORT` (`8080`/`8081`) are proxied/exposed to untrusted networks or restricted to localhost. | Already verified in `bwrap` (`scratch/proof.txt`): `.env.example` has non-empty `REVERB_APP_ID`/`REVERB_APP_KEY`/`REVERB_APP_SECRET`, gitignored workspace `.env` and `.env.production` match `.env.example` on all three fields via `hash_equals`, and `EventsController::verify()` accepts a forged HMAC-SHA256 request signed with those credentials. | Owner must: (1) check the live VPS `.env` (or decrypt `.env.production.encrypted` with `LARAVEL_ENV_ENCRYPTION_KEY`) to confirm whether `REVERB_APP_ID`/`REVERB_APP_KEY`/`REVERB_APP_SECRET` match `.env.example`; and (2) inspect the CloudPanel Nginx vhost and firewall to verify whether `/apps/*` or `REVERB_SERVER_PORT` (`8081`/`8080`) is reachable from the internet instead of only `/app/*` WebSocket upgrades. |
| 6 | `deploy.missing-trust-proxies-and-https-scheme` | Missing TrustProxies and URL::forceScheme('https') causes http:// URL generation when TLS terminates at an upstream proxy | `bootstrap/app.php:16` -> `app/Providers/AppServiceProvider.php:20` -> `app/Notifications/ResetPasswordNotification.php:35` | Live CloudPanel Nginx FastCGI configuration (whether fastcgi_param HTTPS on is set directly by Nginx on port 443) and whether an upstream reverse proxy/CDN terminates TLS and forwards HTTP with X-Forwarded-Proto: https are outside the repository. | Already verified in bwrap sandbox (proof.txt TEST 5): with HTTP_X_FORWARDED_PROTO='https' and HTTPS unset, $request->isSecure() was false, getScheme() was 'http', and ResetPasswordNotification::toMail() generated actionUrl=http://corujinha.bissoli.tech/redefinir-senha/<token>?email=prof.totp%40example.com. | Check the CloudPanel Nginx vhost and fastcgi_params on the VPS (and whether Cloudflare/upstream proxy forwards over HTTP vs HTTPS) to verify whether PHP-FPM receives HTTPS=on or only X-Forwarded-Proto: https. |
| 7 | `student-player-state/classroom-code-enumeration-and-unthrottled-join` | Low-Entropy Classroom Codes and Unthrottled Student Join and Player Endpoints | `routes/web.php:29` -> `routes/web.php:32` -> `app/Http/Controllers/Student/ClassroomPlayerController.php:81` -> `app/Models/Classroom.php:84` | CloudPanel Nginx vhost configuration and any reverse-proxy / WAF rate limiting (e.g., limit_req_zone or Cloudflare throttling) for corujinha.bissoli.tech are not stored in this repository, preventing verification of whether high-volume enumeration across 36^4 codes is blocked at the edge. | Verified in proof.txt TEST 6 that Laravel applies zero throttling in-repo: 15 consecutive GET /sala/AVAL-000X/atividade requests returned 404, GET /sala/AVAL-TEST/atividade returned 302, and 15 consecutive POST /sala/iniciar requests returned 302 with no HTTP 429 responses. | Inspect the CloudPanel Nginx vhost and any upstream CDN/WAF rules on corujinha.bissoli.tech for rate limiting (limit_req) on /sala/iniciar and /sala/*/atividade; if absent or permissive, confirm high-rate enumeration is unmitigated. |

## 6. Hardening Notes & Positive Source Patterns

### 6.1 Defense-in-Depth Hardening Notes (Not Counted as Findings)

The following source observations from Phase 2 hunters do not cross an independent trust boundary with confirmed exploitability on their own, but should be addressed to strengthen defense-in-depth:

1. config/reverb.php:85 sets 'allowed_origins' => ['*'], disabling Origin validation during WebSocket handshakes in Reverb Server::verifyOrigin(); restrict allowed_origins to the application's configured hostname (e.g., parsed from APP_URL) so arbitrary third-party origins cannot open WebSocket connections from visitors' browsers.
2. config/reverb.php:88-96 leaves 'max_connections' => null and 'rate_limiting.enabled' => false by default; configure explicit connection caps and per-connection WebSocket message rate limiting in production.
3. config/reverb.php:90 defaults 'accept_client_events_from' to 'members', allowing subscribers of any shared channel to relay arbitrary 'client-*' messages via ClientEvent::handle even though the application has no client-event listeners; set to 'none' or disable client messaging.
4. app/Http/Controllers/Professor/QuestionController.php:176-183 queries all classrooms linked to an updated question without filtering 'is_active = true', broadcasting QuestionUpdated payloads to closed classrooms unnecessarily.
5. ClassroomPlayerController::start (app/Http/Controllers/Student/ClassroomPlayerController.php:68-71) does not call $request->session()->regenerate() when binding current_attempt_id and current_classroom_code, and returning to /sala via resultado.blade.php:277 leaves prior student attempt keys in the session. Because Laravel's encrypted/signed cookie-only session transport prevents remote session-ID fixation unless an attacker already holds the browser's session cookie (e.g., shared school lab terminals or subdomain cookie injection), adding $request->session()->regenerate() on start() is recommended hardening.
6. ClassroomPlayerController::submitAttempt (app/Http/Controllers/Student/ClassroomPlayerController.php:205-248) does not reject calls when $attempt->status === 'reviewed', allowing a reviewed attempt to be reverted to 'submitted' with an updated completed_at timestamp (note: existing test StudentPlayerTest.php:108-128 intentionally calls submitAttempt twice while status is 'submitted', so a guard should specifically disallow reverting from 'reviewed').
7. QuestionController::store (line 73) and QuestionController::update (line 144) store question support images on the 'public' disk (storage/app/public), which is symlinked into public/storage (.github/workflows/deploy.yml:76); once showSupportImage is authorized, move question images to the private 'local' disk (storage/app/private) so they cannot be accessed via /storage/questions/images/<hash> even if a filename leaks or Nginx autoindex is enabled.
8. QuestionController::destroy (app/Http/Controllers/Professor/QuestionController.php:211-223) deletes questions without checking whether they are attached to assessments or active classrooms (unlike AssessmentController::destroy at lines 166-169), cascading deletes to assessment_question and student_answers and removing the support image file.
9. routes/web.php:104 and 107-109 register full Route::resource routes for QuestionController and AssessmentController without ->except(['show']), exposing professor.perguntas.show and professor.avaliacoes.show to non-existent methods (500 BadMethodCallException).
10. ClassroomController::store (app/Http/Controllers/Professor/ClassroomController.php:61, 75) validates unique:classrooms,code before normalizing $code = strtoupper(trim($validated['code'])), allowing case/whitespace variants to pass validation and trigger a database IntegrityConstraintViolation.
11. app/Models/User.php:53-61 & migration 2026_11_10_151644: users.totp_secret is stored in plaintext without Laravel 'encrypted' cast.
12. app/Http/Controllers/Auth/InvitationController.php:24-26,42-44: invitation_sent_at is recorded on professor invite/resend but never checked for expiration TTL on invite acceptance.
13. routes/web.php:48-56,75: POST /login, POST /login/passkey, POST /cadastro/finalizar/{token}, and POST /seguranca/totp/enable lack throttle middleware.
14. app/Services/PasskeyService.php:171-184 & app/Http/Controllers/Auth/ForgotPasswordController.php:37-42: POST /login/passkey/options returns allowCredentials for active emails and POST /esqueci-minha-senha returns distinct errors for unregistered emails, enabling account enumeration.
15. app/Http/Controllers/Auth/AuthController.php:60-65: Pre-MFA session (totp_auth_user_id) does not call session()->regenerate() upon first-factor completion (though verifyTotp regenerates upon second-factor completion).
16. app/Http/Controllers/Auth/ResetPasswordController.php:73-81: Password reset succeeds on status='pending' invitee users (though AuthController::login, PasskeyService::verifyAssertion, and EnsureUserRole block pending users from authenticating).
17. app/Services/PasskeyService.php:104-153 & app/Http/Controllers/Auth/SecurityController.php:105-127: Passkey registration accepts client-supplied publicKeySpki without verifying attestationObject/authenticatorData or enforcing key strength and matching publicKeyAlg.
18. app/Services/TotpService.php:26-42 & app/Http/Controllers/Auth/AuthController.php:102-108: TotpService::verify does not return false early on empty $secret, and AuthController::verifyTotp does not re-check $user->isTotpEnabled() or $user->isActive() before logging in.
19. config/session.php:172 & bootstrap/app.php:16-31: SESSION_SECURE_COOKIE defaults to null without TrustProxies configured; relies on .env.production.encrypted or Nginx FastCGI HTTPS parameter for Secure cookie flag.
20. app/Http/Controllers/Auth/InvitationController.php:42-97 & app/Services/PasskeyService.php:311: Invitation acceptance update is not wrapped in an atomic pending-status update/transaction, and verifyClientData casts $data['challenge'] to string without checking is_string().
21. .github/workflows/deploy.yml:20 — appleboy/ssh-action@v1.2.0 is pinned to a mutable Git tag instead of a full commit SHA, does not specify an SSH host-key fingerprint, and the workflow/job omits an explicit permissions: {} block.
22. .github/workflows/deploy.yml:29,60-66 — artisan env:decrypt passes --key="$LARAVEL_ENV_ENCRYPTION_KEY" on the CLI (briefly visible in process arguments) instead of relying solely on the exported environment variable; if LARAVEL_ENV_ENCRYPTION_KEY is empty and an older .env exists on the VPS, deployment silently retains the stale .env; and deploy.yml does not remove any stray public/hot file before/after npm run build.
23. bootstrap/app.php & resources/views/components/layouts/*.blade.php — No application-level HTTP security headers (Content-Security-Policy, X-Frame-Options/frame-ancestors, X-Content-Type-Options, Referrer-Policy, Strict-Transport-Security) are configured in Laravel middleware, leaving header enforcement entirely to the unobserved CloudPanel Nginx vhost.
24. public/.htaccess — Apache directives (Options -MultiViews -Indexes) are ignored by Nginx on CloudPanel; Nginx must independently enforce document root at public/ and deny access to dotfiles.
25. app/Console/Commands/CreateSuperAdminCommand.php:23 — Exposes a --password= CLI option that exposes the super-admin password in shell history and process listings if used non-interactively.
26. .env.production:86 — Stores LARAVEL_ENV_ENCRYPTION_KEY inside .env.production itself (and thus inside the decrypted .env on the VPS), increasing secret exposure if .env is ever disclosed.

### 6.2 Positive Source Patterns Observed

- **Storage & Signed File Transfer (`storage-public-and-local-serve`, clean)**: Question upload validation excludes SVG (`mimes:jpeg,png,webp,jpg`), assigns CSPRNG filenames preventing traversal, and Laravel `ServeFile` / `ReceiveFile` routes enforce valid HMAC signatures on the private `local` disk.
- **Cross-Principal HTML Sanitization & DOM Sinks (`html-sanitization-and-dom-sinks`, clean)**: Cross-principal HTML paths escape or sanitize user/question content via `RichTextSanitizer::toHtml()` (strict allowlist + `DOMDocument`), `TextHighlightService`, `DOMPurify`, and `escapeHtml()`; the only unescaped `renderHighlightedText` field (`curH.id`) is confined to the student own session.
- **Professor/Admin Role Isolation & Ownership Helpers**: Admin/Professor web routes enforce `EnsureUserRole` and controllers consistently check `authorizeQuestion()`, `authorizeAssessment()`, and `authorizeClassroom()` before cross-teacher CRUD operations, which led to the rejection of cross-teacher question-deletion corruption (`chain:question-delete-and-assessment-sync-orphan-scoring-corruption`).

## 7. Coverage Ledger Summary

- **Total Audited Units**: 10
  - **Clean (`covered`)**: 2 units (`professor-admin-isolation::storage-public-and-local-serve`, `xss-sanitization-dom::html-sanitization-and-dom-sinks`)
  - **Resolved Candidates (`candidate`)**: 8 units — all 26 candidate fingerprints linked across these 8 units received independent Phase 3 sandbox verdicts and Phase 5 verification (18 `confirmed`, 7 `needs_validation`, 1 `rejected`)
  - **Blocked (`blocked`)**: 0
  - **Deferred (`deferred`)**: 0
- **Final-Clean Coverage Critic Result**: Passed (`stop: true`, `missing_units: []`, `resolved_prior_leads: []`) after Wave 2 added unit 10 (`availability-and-resource-bounds`).
- **Rejected Candidate Note (Coverage Accounting Only)**: `chain:question-delete-and-assessment-sync-orphan-scoring-corruption` was rejected in Phase 3 sandbox execution because `QuestionController::authorizeQuestion()` and `AssessmentController::authorizeAssessment()` enforce teacher ownership, limiting deletion/sync impact to the same authenticated teacher's own data rather than crossing a trust boundary.

### Important Out-of-Scope / Excluded Attack-Class Blocks (From Ledger)

- **reverb-realtime** (`PROTOCOLS-RPC-AND-MESSAGING.md#Message boundary and canonicalization disagreement`): Standard Laravel Echo / Pusher JSON protocol only.
- **availability-and-resource-bounds** (`RESOURCE-EXHAUSTION-AND-AVAILABILITY.md#Decompression and representation amplification`): Already evaluated and excluded under storage-public-and-local-serve; uploaded images are stored directly without server-side transcoding or archive extraction.
- **professor-admin-isolation** (`RESOURCE-EXHAUSTION-AND-AVAILABILITY.md#Decompression and representation amplification`): Images are stored directly without server-side decompression/transcoding pipelines.
- **xss-sanitization-dom** (`CLIENT-SIDE.md#Service-worker and browser-storage attack classes`): No service worker or browser storage authorization cache is used.
- **auth-session-mfa** (`MEMORY-SAFETY-AND-BINARY.md`): Managed PHP/JS runtime with no custom native binary code.
- **deploy-config-obvious** (`CLOUD-AND-DEPLOYMENT.md#Host or control-plane capability exposure`): No Kubernetes or container orchestration manifests exist in repository.
- **auth-session-mfa** (`WEB-PROTOCOL-AND-AUTH.md#JWT verification and claim binding`): Application uses Laravel session cookies and WebAuthn/TOTP, not JWTs.
- **auth-session-mfa** (`WEB-PROTOCOL-AND-AUTH.md#OAuth/OIDC request and callback binding`): No OAuth/OIDC client or provider implementation in codebase.
- **auth-session-mfa** (`WEB-PROTOCOL-AND-AUTH.md#SAML signed-object and assertion binding`): No SAML support in codebase.
- **professor-admin-isolation** (`DATA-ISOLATION-AND-LIFECYCLE.md#Export and backup scope expansion`): No export or backup feature exists in codebase.
- **student-player-state** (`AI-AND-LLM.md`): No AI/LLM calls or agent integrations exist in application code.
