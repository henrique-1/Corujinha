# Confirmed Findings Technical Details (Medium & High) — Corujinha (`run-1`)

This document provides complete repository-relative traces, dummy principal/resource fixtures, bounded target-native reproduction instructions, observed outputs, conditions, and source-level remediation code for all **16** confirmed `medium` and `high` findings (4 High, 12 Medium). The 2 confirmed `low` findings are documented in `REPORT.md`.

---

## 1. [HIGH] MFA disablement, Passkey enrollment, and Passkey deletion require no password or TOTP step-up verification

- **Fingerprint**: `auth/mfa-disable-and-passkey-enroll-missing-step-up`
- **Overall Severity**: **HIGH** (Likelihood: `medium` — Requires an existing authenticated session, after which all MFA downgrade and backdoor enrollment endpoints succeed in a single request. | Impact: `high` — Allows silent removal of TOTP protection, deletion of legitimate Passkeys, and enrollment of an attacker-controlled Passkey that bypasses both password and TOTP on future logins.)
- **Confidence**: `high` — Verified in source and demonstrated via HTTP kernel requests against all three endpoints for a TOTP-enabled user.

### Description & Root Cause
An authenticated user with TOTP enabled can disable TOTP (POST /seguranca/totp/disable), register a new Passkey (POST /seguranca/passkey) that later bypasses both password and TOTP on login (AuthController::passkeyLoginVerify), and delete existing Passkeys (DELETE /seguranca/passkey/{credential}) without re-entering their password or providing a valid TOTP code.

- **Root Cause**: The security management routes in routes/web.php only enforce ['auth', 'role'], and SecurityController methods disableTotp(), passkeyRegisterStore(), and passkeyDestroy() perform credential/MFA mutations without Laravel's password.confirm middleware or any TOTP/password re-verification.
- **Intended Behavior**: Disabling MFA (TOTP), enrolling a new Passkey (which serves as a passwordless + TOTP-bypassing login factor), and deleting Passkeys must require fresh step-up verification (current password and/or active TOTP challenge) bound to the authenticated session.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:76` (`security.* route group`) — Registers POST /seguranca/totp/disable, GET /seguranca/passkey/options, POST /seguranca/passkey, and DELETE /seguranca/passkey/{credential} under ['auth', 'role'] without password.confirm or step-up middleware.
2. **[propagation]** `app/Http/Controllers/Auth/SecurityController.php:84` (`SecurityController::disableTotp`) — Clears totp_secret and totp_enabled_at on Auth::user() without any password or TOTP confirmation.
3. **[propagation]** `app/Http/Controllers/Auth/SecurityController.php:116` (`SecurityController::passkeyRegisterStore`) — Calls PasskeyService::verifyAndStoreRegistration() to persist a new WebauthnCredential for Auth::user() without password or TOTP confirmation.
4. **[sink]** `app/Http/Controllers/Auth/SecurityController.php:138` (`SecurityController::passkeyDestroy`) — Deletes the user's WebauthnCredential after checking only ownership ($credential->user_id !== Auth::id()) without step-up authentication.

### Supporting Source Evidence
- `routes/web.php:72`: Security route group only uses ['auth', 'role'] middleware.
- `app/Http/Controllers/Auth/SecurityController.php:81`: disableTotp() immediately nulls totp_secret and totp_enabled_at.
- `app/Http/Controllers/Auth/SecurityController.php:105`: passkeyRegisterStore() validates only WebAuthn payload fields and registers a new Passkey without password or TOTP verification.
- `app/Http/Controllers/Auth/SecurityController.php:132`: passkeyDestroy() deletes a Passkey without password or TOTP verification.
- `app/Http/Controllers/Auth/AuthController.php:136`: passkeyLoginVerify() authenticates a user directly via Passkey without requiring TOTP, making unauthorized Passkey enrollment an MFA bypass backdoor.

### Conditions & Containment
- **authentication_level**: Requires an already-authenticated session (e.g., stolen session cookie, XSS-driven same-origin request, or brief access to an unlocked terminal).

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: An attacker who obtains an active user session (or temporary access to an unlocked browser) seeks to downgrade MFA or persist access without knowing the victim's password or possessing their TOTP device.
- **Inputs / Payloads**:
  - `GET /seguranca/passkey/options`
  - `POST /seguranca/passkey (id, clientDataJSON, publicKeySpki, publicKeyAlg, name)`
  - `DELETE /seguranca/passkey/1`
  - `POST /seguranca/totp/disable`
- **Bounded Instructions**:
  1. Authenticate as an active TOTP-enabled professor.
  2. Call GET /seguranca/passkey/options and POST /seguranca/passkey with a generated EC P-256 SPKI keypair and zero password/TOTP parameters.
  3. Call DELETE /seguranca/passkey/{id} with zero password/TOTP parameters.
  4. Call POST /seguranca/totp/disable with zero password/TOTP parameters and check $user->isTotpEnabled().
- **Observed Output & Proven Invariant Failure**:
  > For a TOTP-enabled user, POST /seguranca/passkey returned HTTP 200 {"success":true,"id":1} and persisted the Passkey, DELETE /seguranca/passkey/1 returned HTTP 302 and deleted record id=1, and POST /seguranca/totp/disable returned HTTP 302 and cleared TOTP (isTotpEnabled_after=NO), all with zero password or TOTP re-verification.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Require fresh step-up authentication (current password and, if TOTP is enabled, a valid TOTP code, or Laravel's password.confirm middleware combined with an active TOTP check) on disableTotp, passkeyRegisterOptions/passkeyRegisterStore, and passkeyDestroy.

**`app/Http/Controllers/Auth/SecurityController.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 2. [HIGH] Pending TOTP secret and user email are exfiltrated to third-party api.qrserver.com via QR image URL

- **Fingerprint**: `auth/totp-secret-leaked-via-external-qr-url`
- **Overall Severity**: **HIGH** (Likelihood: `high` — Triggered automatically on every TOTP enrollment page load for every user. | Impact: `high` — Discloses the long-lived TOTP shared secret and user email to an external third-party service and URL logs, allowing anyone with access to those logs to forge valid 6-digit MFA codes.)
- **Confidence**: `high` — Directly verified in source and reproduced via HTTP kernel execution showing the exact session secret transmitted in the external QR URL and persisted unchanged to users.totp_secret.

### Description & Root Cause
When an authenticated user without TOTP enabled visits GET /seguranca, SecurityController::index() generates a 160-bit Base32 secret, stores it in session('totp_pending_secret'), constructs an otpauth://totp/ URI containing the plaintext secret and user email, and renders an <img> tag whose src points to https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=... When the user subsequently verifies a code at POST /seguranca/totp/enable, that exact same secret is persisted to users.totp_secret without rotation.

- **Root Cause**: resources/views/auth/security.blade.php embeds the full otpauth:// URI (including the unencrypted Base32 TOTP seed and user email) into the query string of an external third-party QR rendering URL (https://api.qrserver.com/v1/create-qr-code/) instead of generating the QR code locally on the server or client.
- **Intended Behavior**: TOTP enrollment QR codes should be generated locally (server-side SVG/PNG or client-side JS canvas/SVG) so the shared TOTP secret never leaves the origin or appears in external URLs, third-party server logs, or browser/proxy query logs.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:74` (`GET /seguranca route`) — Routes authenticated users to SecurityController::index().
2. **[propagation]** `app/Http/Controllers/Auth/SecurityController.php:41` (`SecurityController::index`) — Generates totp_pending_secret (line 37), stores it in the session (line 38), and builds $totpUri via TotpService::getOtpAuthUrl($user->name, $user->email, $pendingSecret).
3. **[propagation]** `resources/views/auth/security.blade.php:63` (`security.blade.php QR image element`) — Renders <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($totpUri) }}">, causing the user's browser to send the plaintext TOTP secret in a GET query string to api.qrserver.com.
4. **[sink]** `app/Http/Controllers/Auth/SecurityController.php:69` (`SecurityController::enableTotp`) — Persists the exact same totp_pending_secret into users.totp_secret without rotation.

### Supporting Source Evidence
- `app/Http/Controllers/Auth/SecurityController.php:35`: Stores generated TOTP seed in session('totp_pending_secret') and passes $totpUri to the view.
- `app/Services/TotpService.php:52`: Formats otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30 with plaintext $secret and user email.
- `resources/views/auth/security.blade.php:63`: Outputs https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode($totpUri) }} directly in an <img> src attribute.
- `app/Http/Controllers/Auth/SecurityController.php:69`: Writes the unrotated $pendingSecret directly to users.totp_secret upon POST /seguranca/totp/enable.

### Conditions & Containment
- **authentication_level**: Requires an authenticated active user (professor or super_admin) who does not yet have TOTP enabled.
- **third_party_dependency**: Browser fetches the rendered <img> src from https://api.qrserver.com, exposing the query string to that third party and any intermediary proxies/logs.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: Third-party QR service operator (api.qrserver.com), intermediary TLS/HTTP proxy logger, or local browser history observer inspecting requests triggered by GET /seguranca.
- **Inputs / Payloads**:
  - `GET /seguranca`
  - `POST /seguranca/totp/enable (code=<6-digit TOTP>)`
- **Bounded Instructions**:
  1. Authenticate as an active professor without TOTP enabled and request GET /seguranca.
  2. Extract the <img src> URL from the HTML response and URL-decode its data query parameter.
  3. Submit a valid 6-digit TOTP code generated from that secret to POST /seguranca/totp/enable and compare users.totp_secret against the secret leaked in the img src URL.
- **Observed Output & Proven Invariant Failure**:
  > GET /seguranca returned HTTP 200 with <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=otpauth%3A%2F%2Ftotp%2FCorujinha%3Aqr-leak%40example.com%3Fsecret%3DI2DJ7D24ZEI3XUDQP2KLGWMOMKPG5LXS%26issuer%3DCorujinha%26algorithm%3DSHA1%26digits%3D6%26period%3D30">, matching session('totp_pending_secret') (I2DJ7D24ZEI3XUDQP2KLGWMOMKPG5LXS) and the value subsequently persisted to users.totp_secret on POST /seguranca/totp/enable.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Remove the external api.qrserver.com image URL and generate the TOTP QR code locally (e.g., server-side inline SVG using bacon/bacon-qr-code or client-side JavaScript QR rendering from $totpUri), and add a Content-Security-Policy restricting img-src.

**`resources/views/auth/security.blade.php`**:
```php
<div class="mx-auto inline-block rounded-lg shadow-xs border border-royal-purple-200 bg-white p-2">
    {!! $totpQrSvg !!}
</div>
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 3. [HIGH] Public Reverb Channels Expose Professor PII and Classroom Access Codes Without Authentication

- **Fingerprint**: `reverb-realtime:public-admin-and-professor-channels-leak-pii-and-codes`
- **Overall Severity**: **HIGH** (Likelihood: `high` — The channel names (`admin.professores` and sequential `professor.{id}`) are static or trivially enumerable, `allowed_origins` is `['*']`, and no subscription authentication is performed. | Impact: `high` — Passive unauthenticated listeners capture Brazilian CPF numbers, emails, names, and TOTP status of professors as well as live classroom access codes that allow joining active assessments.)
- **Confidence**: `high` — Directly verified in event classes, `routes/channels.php`, `config/reverb.php`, Reverb's `ChannelBroker`/`Channel` source, and reproduced end-to-end against Reverb's protocol classes in the `bwrap` sandbox.

### Description & Root Cause
The application broadcasts professor personal data (name, email, formatted CPF, activation status, and TOTP status), professor classroom listings (including active classroom access codes and settings), and student join events over unauthenticated public Reverb channels (`admin.professores` and `professor.{user_id}`). Because `routes/channels.php` defines no authorization callbacks for these channels and `config/reverb.php` sets `allowed_origins` to `['*']`, any unauthenticated WebSocket client can subscribe to `admin.professores` or enumerate sequential `professor.{id}` channels and receive all emitted payloads.

- **Root Cause**: `ProfessorListUpdated`, `ClassroomListUpdated`, and `StudentJoinedClassroom` return `Illuminate\Broadcasting\Channel` instances instead of `PrivateChannel`, while `routes/channels.php` only authorizes `App.Models.User.{id}` and `config/reverb.php` allows `['*']` origins. In Reverb (`ChannelBroker::create`), non-`private-` / non-`presence-` channels instantiate `Laravel\Reverb\Protocols\Pusher\Channels\Channel`, whose `subscribe()` method performs no signature verification.
- **Intended Behavior**: Admin-scoped (`admin.professores`) and professor-scoped (`professor.{id}`) broadcast events should use `PrivateChannel` (`private-admin.professores`, `private-professor.{id}`), enforce `super_admin` / matching `user_id` authorization in `routes/channels.php`, and restrict `allowed_origins` in `config/reverb.php` to the application host.

### Ordered Trace
1. **[entrypoint]** `app/Http/Controllers/Admin/ProfessorController.php:65` (`ProfessorController::store`) — Dispatches `ProfessorListUpdated` via `BroadcastService::dispatch()` on professor creation (also dispatched at lines 88, 107, 126, and `InvitationController::accept` line 99 upon CPF/password activation).
2. **[propagation]** `app/Http/Controllers/Professor/ClassroomController.php:87` (`ClassroomController::store`) — Dispatches `ClassroomListUpdated` via `BroadcastService::dispatch()` on classroom creation/update/deletion/closure (lines 87, 155, 312, 330); `ClassroomPlayerController::start` line 66 also dispatches `StudentJoinedClassroom`.
3. **[propagation]** `app/Events/ProfessorListUpdated.php:30` (`ProfessorListUpdated::broadcastOn`) — Targets public `Channel('admin.professores')` and serializes `id`, `name`, `email`, formatted `cpf`, `is_active`, and `is_totp_enabled` in `broadcastWith()` (lines 47-60).
4. **[propagation]** `app/Events/ClassroomListUpdated.php:32` (`ClassroomListUpdated::broadcastOn`) — Targets public `Channel('professor.'.$this->classroom->user_id)` and serializes `id`, `name`, `code`, `assessment_title`, review settings, `is_active`, and `student_attempts_count` (lines 49-65).
5. **[sink]** `vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php:67` (`Channel::subscribe`) — Accepts any WebSocket client subscription without checking `$auth` (unlike `PrivateChannel` via `InteractsWithPrivateChannels::verify`), and `EventDispatcher::dispatchSynchronously` delivers payloads to all subscribed sockets.

### Supporting Source Evidence
- `app/Events/ProfessorListUpdated.php:30`: Uses `new Channel('admin.professores')` and includes formatted `cpf` (`CpfRule::format`), `email`, `name`, and `is_totp_enabled` in `broadcastWith()` (lines 47-60).
- `app/Events/ClassroomListUpdated.php:32`: Uses `new Channel('professor.'.$this->classroom->user_id)` and includes `code` (join code) and classroom metadata in `broadcastWith()` (lines 49-65).
- `app/Events/StudentJoinedClassroom.php:33`: Broadcasts `student_name`, `status`, `started_at`, and `score` to public `Channel('professor.'.$this->attempt->classroom->user_id)`.
- `routes/channels.php:5`: Only defines authorization for `App.Models.User.{id}`; no channel callbacks exist for `admin.professores` or `professor.{id}`.
- `config/reverb.php:85`: Sets `'allowed_origins' => ['*']`, permitting cross-origin WebSocket handshakes in `Server::verifyOrigin()` (`vendor/laravel/reverb/src/Protocols/Pusher/Server.php:194-196`).
- `vendor/laravel/reverb/src/Protocols/Pusher/Channels/ChannelBroker.php:20`: Instantiates unauthenticated `Channel` for any channel name not prefixed with `private`, `presence`, or `cache`.

### Conditions & Containment
- **system_configuration**: Requires `BROADCAST_CONNECTION=reverb` (set in `.env.example:68`, workspace `.env`, and gitignored workspace `.env.production`) and the Reverb server process running (restarted in `.github/workflows/deploy.yml:84`).
- **network_routing**: Requires client reachability to the Reverb WebSocket endpoint (`/app/{appKey}`), which is the same endpoint required for the application's real-time UI features (`resources/js/echo.js`).

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: Unauthenticated external WebSocket client connecting from an arbitrary Origin (`https://untrusted-attacker.example`) using the public `VITE_REVERB_APP_KEY` embedded in the frontend bundle.
- **Inputs / Payloads**:
  - `{"event":"pusher:subscribe","data":{"channel":"admin.professores"}}`
  - `{"event":"pusher:subscribe","data":{"channel":"professor.1"}}`
- **Bounded Instructions**:
  1. Open a WebSocket connection to `/app/{VITE_REVERB_APP_KEY}` with any `Origin` header.
  2. Send `pusher:subscribe` frames for `admin.professores` and sequential `professor.{id}` channels without an `auth` signature.
  3. Wait for `professores.updated`, `salas.updated`, or `student.joined` frames emitted when admins invite/manage professors, professors activate accounts with CPF or create/update classrooms, or students join rooms.
- **Observed Output & Proven Invariant Failure**:
  > In the `bwrap` sandbox test (`scratch/proof.txt`), `Pusher\Server::open()` accepted `Origin: https://untrusted-attacker.example` (`pusher:connection_established`), `EventHandler::subscribe()` returned `pusher_internal:subscription_succeeded` for `admin.professores` and `professor.1` with no `auth` token (while rejecting `private-admin.professores` with `4009`), and `EventDispatcher::dispatchSynchronously()` delivered the full `professores.updated` (including `email`, `cpf: 123.456.789-09`, `is_totp_enabled`), `salas.updated` (including `code: AVAL-DUMY`), and `student.joined` JSON frames to the unauthenticated socket.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Convert `ProfessorListUpdated`, `ClassroomListUpdated`, and the professor-targeted channel in `StudentJoinedClassroom` to `PrivateChannel`, register strict authorization callbacks in `routes/channels.php` (`super_admin` for `private-admin.professores`, matching user ID for `private-professor.{id}`), update `resources/js/professors-monitor.js` and `resources/js/classrooms-list-monitor.js` to use `Echo.private(...)`, and restrict `config/reverb.php` `allowed_origins` to the configured app host.

**`app/Events/ProfessorListUpdated.php`**:
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('admin.professores'),
    ];
}
```

**`app/Events/ClassroomListUpdated.php`**:
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('professor.'.$this->classroom->user_id),
    ];
}
```

**`routes/channels.php`**:
```php
Broadcast::channel('admin.professores', function ($user) {
    return $user->isActive() && $user->isSuperAdmin();
});

Broadcast::channel('professor.{id}', function ($user, int $id) {
    return $user->isActive() && ((int) $user->id === $id || $user->isSuperAdmin());
});
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 4. [HIGH] Mid-Exam Answer Key and Feedback Disclosure via Unauthenticated Attempt State in ClassroomPlayerController::showResult

- **Fingerprint**: `student-player-state/showresult-mid-exam-gabarito-disclosure`
- **Overall Severity**: **HIGH** (Likelihood: `high` — Any student in an active classroom can visit /sala/{code}/resultado directly in their browser with zero special tooling. | Impact: `high` — Completely undermines assessment integrity by disclosing all correct options, feedbacks, and highlight expectations mid-exam while still allowing answer submission.)
- **Confidence**: `high` — Directly verified in source and reproduced end-to-end in the local Laravel HTTP kernel sandbox (proof.txt TEST 1).

### Description & Root Cause
An active student session in an open classroom can visit GET /sala/{code}/resultado while their StudentAttempt is still in_progress, receiving the full answer key (Gabarito Correto), option-level pedagogical feedbacks, and expected command/clue highlight snippets, then continue submitting answers via POST /sala/{code}/atividade/responder.

- **Root Cause**: ClassroomPlayerController::showResult() only verifies that a StudentAttempt exists in session for the requested classroom and omits checks on $attempt->status (e.g., 'submitted' or 'reviewed') and $classroom->is_active before rendering resources/views/estudante/resultado.blade.php.
- **Intended Behavior**: GET /sala/{code}/resultado should only render the answer key, expected highlights, and option feedback once the classroom is closed or the student has completed the assessment workflow (status='reviewed', or status='submitted' when collective_review_enabled is false).

### Ordered Trace
1. **[entrypoint]** `routes/web.php:37` (`Route::get('/resultado')`) — Maps GET /sala/{code}/resultado to ClassroomPlayerController::showResult with no attempt-completion middleware.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:366` (`ClassroomPlayerController::showResult`) — Resolves $classroom and $attempt via getAttempt($classroom) without checking $attempt->status or $classroom->is_active, then loads assessment.questions.options.
3. **[sink]** `resources/views/estudante/resultado.blade.php:256` (`resultado.blade.php`) — Renders 'Gabarito Correto', option feedbacks ($opt->feedback), and expected_commands/expected_clues/command_feedback/clue_feedback to the student.

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:366`: showResult() checks only if (!$attempt) on lines 371-373 and never inspects $attempt->status or $classroom->is_active.
- `resources/views/estudante/resultado.blade.php:155`: Displays expected_commands, command_feedback, expected_clues, clue_feedback, 'Gabarito Correto' badge (line 257), and option feedbacks (line 265).

### Conditions & Containment
- **data_state**: Requires an active classroom (is_active = true) and a started student session (current_attempt_id in session, status = 'in_progress').

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: A student joins an active classroom via POST /sala/iniciar and navigates directly to GET /sala/{code}/resultado before submitting answers.
- **Inputs / Payloads**:
  - `POST /sala/iniciar (code=AVAL-TEST, student_name=Aluno Teste 1)`
  - `GET /sala/AVAL-TEST/resultado`
  - `POST /sala/AVAL-TEST/atividade/responder (question_id=1, answer=B)`
- **Bounded Instructions**:
  1. Join classroom AVAL-TEST via POST /sala/iniciar to obtain a session with current_attempt_id in status 'in_progress'.
  2. Send GET /sala/AVAL-TEST/resultado using the same session cookie.
  3. Observe HTTP 200 with 'Gabarito Correto', expected highlight feedbacks, and option feedbacks, then submit the correct answer via POST /sala/AVAL-TEST/atividade/responder.
- **Observed Output & Proven Invariant Failure**:
  > GET /sala/AVAL-TEST/resultado returned HTTP 200 while status=in_progress and classroom_active=true, exposing Gabarito Correto, Expected command feedback secret, Expected clue feedback secret, and Devolutiva da alternativa B; subsequent POST /sala/AVAL-TEST/atividade/responder returned HTTP 200 {"success":true}.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Gate ClassroomPlayerController::showResult() so results and answer keys are only served when !$classroom->is_active or when the attempt has reached its terminal state ($attempt->status === 'reviewed', or $attempt->status === 'submitted' when !$classroom->collective_review_enabled), redirecting incomplete attempts back to the player or review step.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 5. [MEDIUM] Passkey login bypasses TOTP MFA without verifying the WebAuthn User Verified (UV) flag

- **Fingerprint**: `auth/passkey-assertion-missing-uv-flag-downgrades-mfa`
- **Overall Severity**: **MEDIUM** (Likelihood: `medium` — Requires access to an enrolled authenticator or client able to emit UP=1, UV=0 assertions, facilitated by the server advertising userVerification='preferred'. | Impact: `high` — Bypasses TOTP MFA on professor or super_admin accounts, granting a full authenticated session from presence-only assertions.)
- **Confidence**: `high` — Verified directly in PasskeyService.php and AuthController.php and reproduced end-to-end against POST /login/passkey in the isolated bwrap test harness.

### Description & Root Cause
Password login in AuthController::login() enforces a TOTP challenge when User::isTotpEnabled() is true, whereas passkey login in AuthController::passkeyLoginVerify() authenticates the user directly without TOTP. However, PasskeyService::generateAuthenticationOptions() only sets userVerification='preferred' and PasskeyService::verifyAssertion() discards session('webauthn_login_uv') and validates only the User Present (UP, bit 0 / 0x01) flag in authenticatorData without checking the User Verified (UV, bit 2 / 0x04) flag. Consequently, an assertion signed with UP=1 and UV=0 (no biometric/PIN verification) logs directly into a TOTP-enabled account and bypasses TOTP.

- **Root Cause**: PasskeyService::generateAuthenticationOptions() sets 'userVerification' => 'preferred' and PasskeyService::verifyAssertion() calls session()->forget('webauthn_login_uv') and checks only ($flags & 0x01) === 0 (UP) while omitting any check of ($flags & 0x04) === 0 (UV), while AuthController::passkeyLoginVerify() skips the TOTP challenge unconditionally.
- **Intended Behavior**: When passkey authentication is allowed to substitute for password + TOTP MFA, the server must request userVerification='required' and reject assertions whose authenticatorData flags lack the User Verified bit (0x04), or else require TOTP verification whenever UV=0.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:50` (`guest route group`) — Exposes POST /login/passkey mapped to AuthController::passkeyLoginVerify.
2. **[propagation]** `app/Services/PasskeyService.php:190` (`PasskeyService::generateAuthenticationOptions`) — Issues WebAuthn options with 'userVerification' => 'preferred' instead of 'required' and stores 'webauthn_login_uv' => 'preferred' in session.
3. **[propagation]** `app/Services/PasskeyService.php:243` (`PasskeyService::verifyAssertion`) — Discards 'webauthn_login_uv' at line 204 and checks only ($flags & 0x01) === 0 (User Present), omitting any check for the User Verified bit ($flags & 0x04).
4. **[sink]** `app/Http/Controllers/Auth/AuthController.php:150` (`AuthController::passkeyLoginVerify`) — Calls Auth::login($user, ...) and regenerates the session directly for TOTP-enabled users without enforcing TOTP or checking UV.

### Supporting Source Evidence
- `app/Services/PasskeyService.php:168`: generateAuthenticationOptions sets 'webauthn_login_uv' => 'preferred' and returns 'userVerification' => 'preferred' at line 190.
- `app/Services/PasskeyService.php:204`: verifyAssertion calls session()->forget('webauthn_login_uv') without enforcing it, and lines 242-245 check only ($flags & 0x01) === 0.
- `app/Http/Controllers/Auth/AuthController.php:60`: Password login checks $user->isTotpEnabled() and redirects to login.totp, whereas passkeyLoginVerify (lines 147-151) logs in directly.

### Conditions & Containment
- **data_state**: Target user has an active WebauthnCredential registered and TOTP enabled.
- **user_interaction**: Attacker has physical access to an enrolled authenticator or unlocked device capable of producing a UP=1, UV=0 assertion without PIN/biometric verification.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: An attacker with access to a registered user's roaming security key (without its PIN) or unlocked authenticator client calls POST /login/passkey/options and submits a signed assertion with flags=0x01 (UP=1, UV=0) to POST /login/passkey.
- **Inputs / Payloads**:
  - `authenticatorData = sha256(rpId) || 0x01 (UP=1, UV=0) || pack('N', 2)`
- **Bounded Instructions**:
  1. Create an active professor user with totp_secret and totp_enabled_at set and an enrolled EC P-256 WebauthnCredential.
  2. POST /login/passkey/options and observe userVerification='preferred'.
  3. Sign an assertion with flags=chr(0x01) (UP=1, UV=0) and POST to /login/passkey.
- **Observed Output & Proven Invariant Failure**:
  > In bwrap sandbox (proof.txt TEST 1), POST /login/passkey/options returned HTTP 200 with userVerification=preferred, and POST /login/passkey with flags=0x01 returned HTTP 200 {"success":true,"redirect_url":"http://localhost/professor/salas"}, setting Auth::check()=true and Auth::id()=1 with totp_enabled=true and session_has_totp_auth_user_id=false.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Require userVerification='required' in both PasskeyService::generateRegistrationOptions() and PasskeyService::generateAuthenticationOptions(), and enforce the WebAuthn UV bit (($flags & 0x04) === 0) inside PasskeyService::verifyAssertion() (or route to TOTP when UV is absent).

**`app/Services/PasskeyService.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 6. [MEDIUM] Password reset does not invalidate active database sessions

- **Fingerprint**: `auth/password-reset-leaves-active-sessions-valid`
- **Overall Severity**: **MEDIUM** (Likelihood: `medium` — Occurs deterministically whenever any user resets their password while an active session exists. | Impact: `medium` — Pre-reset or compromised sessions remain valid indefinitely (within session lifetime) after password recovery, preventing effective account remediation.)
- **Confidence**: `high` — Verified in ResetPasswordController::reset(), bootstrap/app.php, and proven via HTTP kernel execution with SQLite database sessions.

### Description & Root Cause
When SESSION_DRIVER=database (the application default in config/session.php and .env.example), completing a password reset via POST /redefinir-senha (ResetPasswordController::reset) updates the user's password and remember_token, but does not delete existing rows in the sessions table for that user and AuthenticateSession middleware is not enabled. Consequently, an already-authenticated session from before the password reset remains valid and continues accessing protected routes such as GET /professor/salas with HTTP 200.

- **Root Cause**: ResetPasswordController::reset() only updates password and remember_token inside the Password::reset() callback without purging active rows in the sessions table, and bootstrap/app.php does not register Laravel's AuthenticateSession middleware.
- **Intended Behavior**: Resetting a user's password must invalidate all existing authenticated sessions for that user (both deleting rows from the sessions table where user_id matches and rotating remember_token, or enforcing AuthenticateSession) so compromised sessions are immediately terminated.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:62` (`POST /redefinir-senha route`) — Routes password reset submissions to ResetPasswordController::reset.
2. **[propagation]** `app/Http/Controllers/Auth/ResetPasswordController.php:73` (`ResetPasswordController::reset`) — Updates password and remember_token via Password::reset() (lines 76-79) without deleting existing user rows in the sessions table or calling Auth::logoutOtherDevices().
3. **[propagation]** `bootstrap/app.php:16` (`withMiddleware configuration`) — Does not enable Illuminate\Session\Middleware\AuthenticateSession, so existing database sessions are never invalidated on password hash changes.
4. **[sink]** `routes/web.php:113` (`GET /professor/salas route`) — Continues serving HTTP 200 to the pre-reset session cookie via auth and role:professor middleware.

### Supporting Source Evidence
- `config/session.php:21`: Default session driver is 'database' (and .env.example line 31 sets SESSION_DRIVER=database).
- `app/Http/Controllers/Auth/ResetPasswordController.php:75`: Password::reset() closure updates only password and remember_token; no DB::table('sessions')->where('user_id', $user->id)->delete() call exists.
- `bootstrap/app.php:16`: AuthenticateSession middleware is not configured in web middleware.

### Conditions & Containment
- **system_configuration**: Applies when session.driver is 'database' (the configured default in config/session.php and .env.example).
- **data_state**: User has at least one active authenticated session prior to completing a password reset.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: An attacker holding a previously hijacked or persistent session cookie retains access to the victim's account even after the victim resets their password to recover the account.
- **Inputs / Payloads**:
  - `POST /redefinir-senha (token, email, password, password_confirmation)`
  - `GET /professor/salas (with pre-reset session cookie)`
- **Bounded Instructions**:
  1. Log in as an active professor with SESSION_DRIVER=database to create an authenticated session row in the sessions table and verify GET /professor/salas returns HTTP 200.
  2. From a separate unauthenticated client, submit a valid password reset token and new 15+ char password to POST /redefinir-senha.
  3. Verify the user's password hash changed in the users table and check whether the prior session row still exists in sessions.
  4. Replay the pre-reset session cookie against GET /professor/salas.
- **Observed Output & Proven Invariant Failure**:
  > Before reset, session row qSY1isTFz0W0y3RbE93MoCKslMVKzKOCWncA6K8M (user_id=4) accessed GET /professor/salas with HTTP 200. After POST /redefinir-senha returned HTTP 302 and updated users.password, the pre-reset row remained in the sessions table (user_id=4, total_user_sessions=1) and GET /professor/salas with the pre-reset cookie still returned HTTP 200.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Invalidate all existing database sessions for the user during password reset and/or enable AuthenticateSession middleware in bootstrap/app.php.

**`app/Http/Controllers/Auth/ResetPasswordController.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 7. [MEDIUM] TOTP 6-digit login codes can be replayed across independent sessions within the validity window

- **Fingerprint**: `auth/totp-code-replay-within-validity-window`
- **Overall Severity**: **MEDIUM** (Likelihood: `low` — Requires the attacker to already know the user's password and obtain an active 6-digit TOTP code within its 90-second validity window. | Impact: `medium` — Defeats single-use property of TOTP codes, enabling an attacker to establish an independent authenticated session using an already-consumed OTP.)
- **Confidence**: `high` — Confirmed in TotpService::verify() and reproduced in the HTTP kernel sandbox with two independent sessions authenticating on the same code.

### Description & Root Cause
TotpService::verify() performs stateless HMAC-SHA1 verification across a +/-1 time-slice window (90 seconds) without tracking consumed codes or time slices. AuthController::verifyTotp() does not store or check the last accepted time slice for the user, allowing two independent login sessions for the same TOTP-enabled user to authenticate with the identical 6-digit TOTP code within the same 30-second slice.

- **Root Cause**: Neither TotpService::verify() nor AuthController::verifyTotp() records the last accepted TOTP time slice (e.g., on users or in cache) or rejects codes from a time slice less than or equal to the previously accepted slice.
- **Intended Behavior**: Per RFC 6238 Section 5.2, each accepted TOTP code (and earlier time slices) must be single-use per user so an intercepted or phished 6-digit code cannot be replayed by a second session.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:53` (`POST /login/totp route`) — Routes TOTP login challenge submissions to AuthController::verifyTotp.
2. **[propagation]** `app/Http/Controllers/Auth/AuthController.php:104` (`AuthController::verifyTotp`) — Calls $totpService->verify((string) $user->totp_secret, $request->input('code')) without checking or recording prior code/slice consumption.
3. **[sink]** `app/Services/TotpService.php:35` (`TotpService::verify`) — Loops $i = -$window to +$window ($window = 1) and returns true purely on hash_equals() match without one-time replay state.

### Supporting Source Evidence
- `app/Services/TotpService.php:26`: Stateless TotpService::verify() checks +/-1 30s slices without tracking previously accepted slices or codes.
- `app/Http/Controllers/Auth/AuthController.php:104`: AuthController::verifyTotp() calls Auth::login($user) on true return without persisting any replay-prevention marker.
- `database/migrations/2026_11_10_151644_add_auth_and_security_fields_to_users_table.php:20`: users table schema has totp_secret and totp_enabled_at but no column for last-used TOTP time slice.

### Conditions & Containment
- **data_state**: Target user has TOTP enabled and attacker already possesses valid password credentials (or pre-auth session) plus a recently submitted 6-digit TOTP code.
- **timing_dependency**: Replay must occur within the 90-second (+/- 1 30s slice) acceptance window of the original code.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: An attacker who intercepts, shoulder-surfs, or phishes a victim's password and a single 6-digit TOTP code replays that same code in an independent session after the victim logs in.
- **Inputs / Payloads**:
  - `POST /login (email, password)`
  - `POST /login/totp (code=<same 6-digit code in Session A and Session B>)`
- **Bounded Instructions**:
  1. Start two independent unauthenticated sessions (Session A and Session B) and submit valid email/password to POST /login for the same TOTP-enabled user.
  2. Compute a single 6-digit TOTP code for the current 30s time slice.
  3. Submit that code to POST /login/totp in Session A and verify GET /professor/salas succeeds.
  4. Immediately submit the identical 6-digit code to POST /login/totp in Session B within the same 30s slice and verify GET /professor/salas succeeds.
- **Observed Output & Proven Invariant Failure**:
  > Both Session A and Session B submitted identical code 749458 in time slice 59815108, both received HTTP 302 redirects to /professor/salas, and both accessed GET /professor/salas with HTTP 200.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Persist the last accepted TOTP time slice per user in the database (with row-level locking) or atomically under a cache lock keyed by user ID, reject any TOTP verification whose matching time slice is less than or equal to the last accepted slice, and call that replay-checked method from AuthController::verifyTotp().

**`app/Services/TotpService.php`**:
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

**`app/Http/Controllers/Auth/AuthController.php`**:
```php
if (! $totpService->verifyOnce($user->id, (string) $user->totp_secret, (string) $request->input('code'))) {
    return back()->withErrors([
        'code' => 'Código de autenticação inválido ou expirado. Tente novamente.',
    ]);
}
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 8. [MEDIUM] Unvalidated reviews array in ClassroomPlayerController::finishReview executes one SELECT * FROM questions query per arbitrary key in a single request

- **Fingerprint**: `availability/finish-review-n-plus-one-question-queries`
- **Overall Severity**: **MEDIUM** (Likelihood: `high` — Any unauthenticated student with an active classroom code can trigger arbitrary 1-to-K question query fan-out in a single HTTP request. | Impact: `medium` — Amplifies a single small student HTTP request into thousands of synchronous database queries, consuming PHP-FPM worker time and database connections.)
- **Confidence**: `high` — Exactly 200 sequential SELECT * FROM questions queries were observed in DB::getQueryLog() from a single 200-key finishReview() request.

### Description & Root Cause
ClassroomPlayerController::finishReview() reads $request->input('reviews', []) without any array size or key validation and calls Question::find($questionId) on every key inside a foreach loop before checking whether the student has an existing answer or whether collective review is enabled. A single HTTP POST request with K arbitrary keys triggers K sequential synchronous SELECT * FROM questions queries.

- **Root Cause**: ClassroomPlayerController::finishReview() iterates an unvalidated $request->input('reviews', []) map and invokes Question::find($questionId) inside the loop instead of validating reviews keys/size and batch-loading only questions belonging to $attempt->answers.
- **Intended Behavior**: finishReview() should validate reviews as an array bounded to the assessment's question count (and verify collective_review_enabled), then batch-load relevant questions via whereIn('id', ...) or reuse $existingAnswers->load('question').

### Ordered Trace
1. **[entrypoint]** `routes/web.php:36` (`POST /sala/{code}/revisao/concluir`) — Student review submission route invokes ClassroomPlayerController::finishReview().
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:302` (`ClassroomPlayerController::finishReview`) — Reads $reviews = $request->input('reviews', []) with no array size or key validation.
3. **[sink]** `app/Http/Controllers/Student/ClassroomPlayerController.php:330` (`ClassroomPlayerController::finishReview`) — Executes Question::find($questionId) on every key in $reviews before checking $existingAnswers->get($question->id).

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:302`: $request->input('reviews', []) is passed directly to foreach loops without $request->validate().
- `app/Http/Controllers/Student/ClassroomPlayerController.php:330`: Question::find($questionId) runs unconditionally per key; DB::enableQueryLog() recorded 207 total queries and 201 SELECTs on questions (200 individual WHERE id = ? LIMIT 1 queries) from a single 200-key request.

### Conditions & Containment
- **data_state**: Requires an active classroom code and a student session created via POST /sala/iniciar.
- **environmental_dependency**: Form-encoded payloads are subject to PHP max_input_vars (default 1000), whereas JSON request bodies bypass max_input_vars up to PHP post_max_size / Nginx client_max_body_size.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: Unauthenticated student in an active classroom submitting a single POST /sala/{code}/revisao/concluir request containing hundreds of synthetic reviews keys.
- **Inputs / Payloads**:
  - `POST /sala/C6-TEST/revisao/concluir with reviews[900001..900200][reviewed_answer]=A and reviews[900001..900200][review_reflection]=test`
- **Bounded Instructions**:
  1. Create an active classroom and student attempt in SQLite :memory: inside the bwrap sandbox.
  2. Enable DB::enableQueryLog() and dispatch one POST /sala/C6-TEST/revisao/concluir request with 200 synthetic reviews keys (900001..900200).
  3. Count total queries and SELECT queries targeting the questions table.
- **Observed Output & Proven Invariant Failure**:
  > POST /sala/C6-TEST/revisao/concluir returned HTTP 302 and DB::enableQueryLog() recorded 207 total queries in 30.8 ms, including 201 SELECTs from questions (200 sequential 'select * from "questions" where "questions"."id" = ? limit 1' queries for bindings [900001]..[900200]).

### Source-Level Remediation & Regression Guidance
- **Strategy**: Validate reviews keys/size against the assessment questions (and reject/ignore when collective_review_enabled is false), and batch-load questions with whereIn instead of calling Question::find() inside the loop.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
```php
$existingAnswers = $attempt->answers()->with('question')->get()->keyBy('question_id');
$validated = $request->validate([
    'reviews' => ['nullable', 'array', 'max:'.$existingAnswers->count()],
    'reviews.*.reviewed_answer' => ['nullable', 'in:A,B,C,D,E'],
    'reviews.*.review_reflection' => ['nullable', 'string', 'max:5000'],
]);
// Inside loop: $answerRecord = $existingAnswers->get((int) $questionId); if (! $answerRecord || ! $answerRecord->question) { continue; }
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 9. [MEDIUM] Unbounded markers array in saveAnswer amplifies CPU/DOM work in TextHighlightService::applyHighlights() and Blade marker rendering

- **Fingerprint**: `availability/unbounded-markers-highlight-amplification`
- **Overall Severity**: **MEDIUM** (Likelihood: `medium` — Any student with an active classroom code can submit thousands of markers in one request, though worst-case O(T * K) CPU amplification requires large/multi-node question support_text. | Impact: `medium` — Persists amplification payload that consumes multiple seconds of PHP worker CPU per render on student result views (3 calls per question) and inflates teacher attempt-detail rendering.)
- **Confidence**: `high` — Unbounded saveAnswer acceptance (HTTP 200) and superlinear scaling across M=100, 2,000, and 5,000 markers were directly measured in the sandbox.

### Description & Root Cause
ClassroomPlayerController::saveAnswer() accepts an unbounded markers array and reason string with no array size or marker text length caps. When results or teacher attempt details are viewed, TextHighlightService::applyHighlights() performs O(M * L) substring scans and O(T * K) nested text-node/marker filtering across DOMDocument nodes (3x per question in estudante.resultado), and both resultado.blade.php and tentativa-detalhes.blade.php iterate all M markers to render HTML badges.

- **Root Cause**: ClassroomPlayerController::saveAnswer() validates markers only as ['nullable', 'array'] and reason only as ['required', 'string'] with no maximum item count or byte/character limits, allowing a student to persist thousands of markers that trigger superlinear O(M * L) + O(T * K) DOM/mbstring processing in TextHighlightService::applyHighlights() and O(M) Blade rendering.
- **Intended Behavior**: saveAnswer() should enforce tight upper bounds on markers count (e.g., max:200), marker text length, and reason length, and TextHighlightService::applyHighlights() should avoid O(T * K) full-array scans per DOMText node (e.g., using sorted two-pointer or interval indexing).

### Ordered Trace
1. **[entrypoint]** `routes/web.php:33` (`POST /sala/{code}/atividade/responder`) — Unauthenticated student route calls ClassroomPlayerController::saveAnswer().
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:152` (`ClassroomPlayerController::saveAnswer`) — Validates 'markers' => ['nullable', 'array'] and 'reason' => ['required', 'string'] with no max count or length bounds and stores them in student_answers.
3. **[propagation]** `app/Services/TextHighlightService.php:74` (`TextHighlightService::applyHighlights`) — Iterates all M markers running mb_substr/mb_strpos over fullPlainText (O(M * L)), then iterates all T DOMText nodes filtering all K non-overlapping markers (O(T * K) at lines 135-145).
4. **[sink]** `resources/views/professor/salas/tentativa-detalhes.blade.php:174` (`ClassroomController::showAttempt view`) — Renders a Blade badge element for every stored marker in $ans->initial_markers when the professor views the attempt.

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:151`: Validation rules for reason (['required', 'string']) and markers (['nullable', 'array']) impose no max count or string length limits; a 2,000-marker + 10KB reason saveAnswer request returned HTTP 200.
- `app/Services/TextHighlightService.php:101`: Fallback mb_strpos($fullPlainText, $snippet, 0, 'UTF-8') executes per marker without a marker count cap.
- `app/Services/TextHighlightService.php:141`: Nested array_filter($nonOverlapping, ...) runs inside foreach ($textNodes as $entry), giving O(T * K) complexity; locally scaled from 100.91 ms (M=100, 5.7 KB) to 1164.73 ms (M=2,000, 117.9 KB) to 3268.01 ms (M=5,000, 296.4 KB) on T=5,000 nodes.
- `resources/views/estudante/resultado.blade.php:106`: Calls TextHighlightService::applyHighlights() up to 3 times per question (lines 101, 106, 121) and iterates all $markers at line 133.

### Conditions & Containment
- **data_state**: Requires an active classroom code and a student attempt session; worst-case O(T * K) amplification scales with the number of DOMText nodes T and length L in the question's support_text.
- **environmental_dependency**: Upper ceiling on a single HTTP POST body size is governed by external PHP post_max_size/memory_limit and Nginx client_max_body_size, though a 296 KB JSON payload (5,000 markers) is well below standard defaults.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: Unauthenticated student in an active classroom submitting oversized markers JSON arrays to POST /sala/{code}/atividade/responder.
- **Inputs / Payloads**:
  - `JSON array of 2,000 markers (117,891 bytes) + 10,000-byte reason via POST /sala/C4-TEST/atividade/responder`
  - `Bounded scaling benchmark with M=100 (5,741 bytes), M=2,000 (117,891 bytes), and M=5,000 (296,391 bytes) unique matching markers on 298,890-byte HTML (T=5,000 DOMText nodes)`
- **Bounded Instructions**:
  1. Call POST /sala/C4-TEST/atividade/responder with 2,000 marker objects and a 10,000-byte reason string inside the bwrap sandbox.
  2. Measure TextHighlightService::applyHighlights() wall time and memory delta across M=100, M=2,000, and M=5,000 unique markers on a 5,000-<p> HTML fixture.
- **Observed Output & Proven Invariant Failure**:
  > saveAnswer accepted 2,000 markers + 10KB reason with HTTP 200. TextHighlightService::applyHighlights() scaled superlinearly on T=5,000 nodes: M=100 (5,741 B) took 100.91 ms; M=2,000 (117,891 B) took 1,164.73 ms (+2 MiB); M=5,000 (296,391 B) took 3,268.01 ms (+4 MiB).

### Source-Level Remediation & Regression Guidance
- **Strategy**: Enforce strict max element count and field length validation on markers and reason in saveAnswer(), and replace the O(T * K) inner array_filter in TextHighlightService::applyHighlights() with a single sorted pointer pass.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
```php
'reason' => ['required', 'string', 'max:5000'],
'markers' => ['nullable', 'array', 'max:100'],
'markers.*.text' => ['required', 'string', 'max:1000'],
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 10. [MEDIUM] Unvalidated nested markers array in ClassroomPlayerController::saveAnswer causes persistent HTTP 500 on teacher attempt details and student result views

- **Fingerprint**: `chain:saveAnswer-unvalidated-markers-to-teacher-tentativa-detalhes-500`
- **Overall Severity**: **MEDIUM** (Likelihood: `high` — Any unauthenticated student who enters an active classroom code can submit the malformed JSON payload in a single request. | Impact: `medium` — Persistently breaks the professor's attempt detail view (HTTP 500) for that student attempt and the student's result page, while leaving other classrooms and attempts unaffected.)
- **Confidence**: `high` — Both HTTP 500 responses and exact PHP exceptions were reproduced end-to-end in the sandboxed Laravel HTTP kernel.

### Description & Root Cause
ClassroomPlayerController::saveAnswer() validates the markers parameter only as ['nullable', 'array'] without validating element structure or requiring markers.*.text to be a string. An unauthenticated student in an active classroom can persist a marker whose text field is a nested array, causing HTTP 500 ViewException/TypeError when a professor opens professor.salas.tentativa (tentativa-detalhes.blade.php) and HTTP 500 ViewException/ErrorException when rendering estudante.resultado via TextHighlightService::applyHighlights().

- **Root Cause**: ClassroomPlayerController::saveAnswer() accepts arbitrary nested arrays inside markers without item-level validation (such as markers.*.kind and markers.*.text as strings), persisting non-string values into student_answers.initial_markers. Downstream Blade templates pass $m['text'] to e()/htmlspecialchars() and TextHighlightService::applyHighlights() casts $m['text'] to string, triggering fatal TypeError/ErrorException under PHP 8.
- **Intended Behavior**: saveAnswer() should validate each marker element's shape and scalar types (e.g., markers.* as array, markers.*.kind in command/evidence/clue, and markers.*.text as string), and both TextHighlightService::applyHighlights() and Blade views should safely guard non-string marker fields.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:33` (`POST /sala/{code}/atividade/responder`) — Public student route invokes ClassroomPlayerController::saveAnswer without authentication beyond classroom session attempt.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:152` (`ClassroomPlayerController::saveAnswer`) — Request validation checks only 'markers' => ['nullable', 'array'] with no element or 'markers.*.text' string validation.
3. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:188` (`ClassroomPlayerController::saveAnswer`) — Stores unvalidated $markers array into StudentAnswer::initial_markers JSON column.
4. **[propagation]** `resources/views/professor/salas/tentativa-detalhes.blade.php:176` (`ClassroomController::showAttempt view`) — Renders {{ $m['text'] ?? '' }} via htmlspecialchars(), throwing TypeError when $m['text'] is an array and returning HTTP 500.
5. **[sink]** `app/Services/TextHighlightService.php:75` (`TextHighlightService::applyHighlights`) — Casts (string) ($m['text'] ?? '') during student result rendering (estudante.resultado), throwing ErrorException (Array to string conversion) and returning HTTP 500.

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:152`: 'markers' => ['nullable', 'array'] lacks element and scalar string validation for markers.*.text.
- `app/Http/Controllers/Professor/ClassroomController.php:298`: showAttempt() renders professor.salas.tentativa-detalhes with stored StudentAnswer::initial_markers.
- `resources/views/professor/salas/tentativa-detalhes.blade.php:176`: Blade output {{ $m['text'] ?? '' }} passes array value directly to e()/htmlspecialchars().
- `app/Services/TextHighlightService.php:75`: trim((string) ($m['text'] ?? '')) triggers Array to string conversion ErrorException on nested array.
- `resources/views/estudante/resultado.blade.php:139`: Also echoes {{ $m['text'] ?? '' }} for each marker in $ans->initial_markers.

### Conditions & Containment
- **data_state**: Requires an active classroom code and a student session created via POST /sala/iniciar.
- **user_interaction**: Professor triggers the teacher-side HTTP 500 when opening the affected student's attempt details page (professor.salas.tentativa).

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: Unauthenticated student who joins an active classroom via POST /sala/iniciar and submits JSON to POST /sala/{code}/atividade/responder.
- **Inputs / Payloads**:
  - `{"question_id":1,"answer":"B","reason":"Minha justificativa","markers":[{"kind":"command","text":["nested"]},{"kind":"evidence","text":"ok"}],"time_spent":5000}`
- **Bounded Instructions**:
  1. Create a professor, question, assessment, active classroom, and student attempt in SQLite :memory: inside the bwrap sandbox.
  2. Dispatch JSON POST /sala/C1-TEST/atividade/responder with session current_attempt_id and markers: [['kind' => 'command', 'text' => ['nested']], ['kind' => 'evidence', 'text' => 'ok']].
  3. Authenticate as the owning professor and dispatch GET /professor/salas/{classroom}/tentativas/{attempt}.
  4. Dispatch GET /sala/C1-TEST/resultado with the student attempt session and also invoke TextHighlightService::applyHighlights() directly.
- **Observed Output & Proven Invariant Failure**:
  > POST /sala/C1-TEST/atividade/responder returned HTTP 200 {"success":true} and persisted [{"kind":"command","text":["nested"]},{"kind":"evidence","text":"ok"}] in student_answers.initial_markers. GET /professor/salas/{id}/tentativas/{id} returned HTTP 500 with Illuminate\View\ViewException wrapping TypeError: htmlspecialchars(): Argument #1 ($string) must be of type string, array given at helpers.php:138. GET /sala/C1-TEST/resultado returned HTTP 500 with Illuminate\View\ViewException wrapping ErrorException: Array to string conversion at TextHighlightService.php:75.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Add strict item-level validation for markers in ClassroomPlayerController::saveAnswer() enforcing scalar string types and allowed fields, and add defensive is_array/is_string checks in TextHighlightService::applyHighlights() and Blade marker loops.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
```php
'markers' => ['nullable', 'array', 'max:200'],
'markers.*' => ['required', 'array'],
'markers.*.kind' => ['required', 'string', 'in:command,evidence,clue'],
'markers.*.target' => ['nullable', 'string', 'in:title,support,source,prompt'],
'markers.*.text' => ['required', 'string', 'max:2000'],
'markers.*.start' => ['nullable', 'integer', 'min:0'],
'markers.*.end' => ['nullable', 'integer', 'min:0'],
```

**`app/Services/TextHighlightService.php`**:
```php
if (! is_array($m) || ! is_string($m['text'] ?? null)) {
    continue;
}
$snippet = trim($m['text']);
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 11. [MEDIUM] Unauthenticated Sequential IDOR on Private and Draft Question Support Images via /midia/questoes/{question}

- **Fingerprint**: `professor-admin-isolation:midia-questao-unauthenticated-idor`
- **Overall Severity**: **MEDIUM** (Likelihood: `high` — Public endpoint accepting predictable sequential integer Question IDs with zero authentication. | Impact: `medium` — Discloses private and unpublished draft teacher question images across tenants and outside active exams.)
- **Confidence**: `high` — Directly verified in source and reproduced unauthenticated in proof.txt TEST 7.

### Description & Root Cause
GET /midia/questoes/{question} streams any question's support_image_path from public storage without authentication, professor ownership authorization, or student classroom enrollment verification, allowing anyone to enumerate sequential integer question IDs and download images belonging to private, draft, or other teachers' questions.

- **Root Cause**: routes/web.php registers GET /midia/questoes/{question} as a public route without middleware, and QuestionController::showSupportImage() only checks hasImageSupport() and Storage::disk('public')->exists() before streaming the file.
- **Intended Behavior**: Question support images should only be accessible to the owning professor, super-admins, or students with an active session in an open classroom whose assessment includes that question.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:25` (`Route::get('/midia/questoes/{question}')`) — Public route binding Question model by sequential integer ID without auth or student session checks.
2. **[sink]** `app/Http/Controllers/Professor/QuestionController.php:193` (`QuestionController::showSupportImage`) — Streams Storage::disk('public')->response($question->support_image_path) for any question with an image, regardless of attachment to any assessment or classroom.

### Supporting Source Evidence
- `routes/web.php:25`: midia.questao route is placed in the public unauthenticated group.
- `app/Http/Controllers/Professor/QuestionController.php:195`: showSupportImage() performs no Auth::check(), authorizeQuestion(), or classroom attempt/assessment membership check.

### Conditions & Containment
- **data_state**: Requires at least one Question record with a non-null support_image_path pointing to an existing file on the public disk.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: An unauthenticated remote user enumerates integer IDs on GET /midia/questoes/{id}.
- **Inputs / Payloads**:
  - `GET /midia/questoes/5`
- **Bounded Instructions**:
  1. Create a draft Question owned by Teacher B not attached to any Assessment or Classroom, backed by dummy image file questions/images/dummy.jpg.
  2. Send an unauthenticated GET /midia/questoes/{id} request without login or student classroom cookies.
- **Observed Output & Proven Invariant Failure**:
  > Unauthenticated GET /midia/questoes/5 returned HTTP 200 with Content-Type: image/jpeg and streamed the exact 48-byte dummy image from Teacher B's draft question.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Require either an authenticated owning professor/super-admin or a valid student session whose active classroom assessment contains the requested question, and move private question uploads off the public symlinked disk.

**`app/Http/Controllers/Professor/QuestionController.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 12. [MEDIUM] Public Classroom Channel `classroom.{id}` Leaks Question Content, Room Access Codes, and Peer Student Telemetry

- **Fingerprint**: `reverb-realtime:public-classroom-channel-leaks-questions-and-student-telemetry`
- **Overall Severity**: **MEDIUM** (Likelihood: `high` — Classroom IDs are sequential integers and every active student browser already receives peer telemetry on the open WebSocket without any extra action. | Impact: `medium` — Exposes peer student names and live scores to classmates and leaks full question content and classroom access codes on update/closure to anonymous subscribers (note: `toPlayerPayload()` omits `correct_option` and feedback fields).)
- **Confidence**: `high` — Verified across all four event classes, `Question::toPlayerPayload()`, JS subscribers, and reproduced locally in `bwrap` against Reverb's server and channel classes.

### Description & Root Cause
Four events (`QuestionUpdated`, `ClassroomClosed`, `StudentJoinedClassroom`, and `StudentAttemptUpdated`) broadcast onto the unauthenticated public Reverb channel `classroom.{id}` using sequential integer classroom IDs. Because both the professor monitoring panel (`classroom-monitor.js`) and the student player/review views (`classroom-player.js`, `revisao.blade.php`) subscribe to the same public `classroom.{id}` channel, any student participating in a room automatically receives peer students' names, attempt IDs, statuses, and scores, and any unauthenticated WebSocket client enumerating `classroom.{id}` can intercept edited question payloads (`support_text`, `prompt`, `options`), classroom `code` values on closure, and live student telemetry.

- **Root Cause**: `QuestionUpdated`, `ClassroomClosed`, `StudentJoinedClassroom`, and `StudentAttemptUpdated` all broadcast on public `Illuminate\Broadcasting\Channel('classroom.'.$id)` without channel authorization, and student-only telemetry (`StudentJoinedClassroom`, `StudentAttemptUpdated`) intended for the teacher monitor (`classroom-monitor.js`) is emitted onto the same public `classroom.{id}` channel listened to by student browsers (`classroom-player.js:776`, `revisao.blade.php:223`).
- **Intended Behavior**: Teacher-only student telemetry (`StudentJoinedClassroom`, `StudentAttemptUpdated`) should be emitted only on a `PrivateChannel` restricted to the classroom owner / `super_admin` (e.g., `private-classroom.monitor.{id}`), and student-facing classroom channels should not be enumerable by unauthenticated third parties outside an active classroom session (or should use non-guessable channel tokens / authorized student channel endpoints).

### Ordered Trace
1. **[entrypoint]** `app/Http/Controllers/Professor/QuestionController.php:182` (`QuestionController::update`) — Dispatches `QuestionUpdated($pergunta, $classroomIds)` whenever a question linked to a classroom is updated.
2. **[propagation]** `app/Http/Controllers/Professor/ClassroomController.php:329` (`ClassroomController::closeClassroomAndNotify`) — Dispatches `ClassroomClosed($sala)` when a professor closes a classroom or enters devolutiva.
3. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:66` (`ClassroomPlayerController::start`) — Dispatches `StudentJoinedClassroom($attempt)` on student entry; `saveAnswer` (line 197), `submitAttempt` (line 233), and `finishReview` (line 358) dispatch `StudentAttemptUpdated($attempt)`.
4. **[propagation]** `app/Events/QuestionUpdated.php:34` (`QuestionUpdated::broadcastOn`) — Targets `new Channel('classroom.'.$id)` with `toPlayerPayload()` (`support_title`, `support_text`, `support_image_url`, `prompt`, `options`). `ClassroomClosed` (line 26), `StudentJoinedClassroom` (line 32), and `StudentAttemptUpdated` (line 29) target the same public `classroom.{id}` channel.
5. **[sink]** `vendor/laravel/reverb/src/Protocols/Pusher/Channels/Channel.php:67` (`Channel::subscribe`) — Allows any connected WebSocket client (unauthenticated external listener or peer student browser) to subscribe to `classroom.{id}` without authorization and receive all four event types.

### Supporting Source Evidence
- `app/Events/QuestionUpdated.php:54`: Emits `question->toPlayerPayload()` (`app/Models/Question.php:126-146`) including `support_title`, `support_text`, `support_image_url`, `prompt`, and `options` A–E on public `Channel('classroom.'.$id)`.
- `app/Events/ClassroomClosed.php:47`: Emits `classroom_id`, `code`, and `redirect_url` (`/sala/{code}/resultado`) on public `Channel('classroom.'.$this->classroom->id)`.
- `app/Events/StudentJoinedClassroom.php:32`: Emits `attempt` (`id`, `student_name`, `status`, `started_at`, `score`) on public `Channel('classroom.'.$this->attempt->classroom_id)`.
- `app/Events/StudentAttemptUpdated.php:29`: Emits `attempt` (`id`, `status`, `score`, `reviewed_at`, `completed_at`) on public `Channel('classroom.'.$this->attempt->classroom_id)`.
- `resources/js/classroom-player.js:776`: Student browser subscribes to `window.Echo.channel('classroom.' + config.classroomId)` alongside professor browser (`resources/js/classroom-monitor.js:20`) and review page (`resources/views/estudante/revisao.blade.php:223`).

### Conditions & Containment
- **system_configuration**: Requires `BROADCAST_CONNECTION=reverb` and Reverb WebSocket server running (active in `.env.example:68`, workspace `.env.production`, and `.github/workflows/deploy.yml:84`).
- **network_routing**: Requires reachability of Reverb's `/app/{appKey}` WebSocket endpoint; peer student exposure requires only the normal student assessment connection.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: Either (1) an unauthenticated external WebSocket client enumerating `classroom.1`, `classroom.2`, ... or (2) an unauthenticated student already connected to `classroom.{id}` via `classroom-player.js` inspecting WebSocket frames.
- **Inputs / Payloads**:
  - `{"event":"pusher:subscribe","data":{"channel":"classroom.1"}}`
- **Bounded Instructions**:
  1. Connect to Reverb WebSocket `/app/{VITE_REVERB_APP_KEY}` (or open DevTools Network WS inspector as an active student in a classroom).
  2. Send `pusher:subscribe` for `classroom.1` (and sequential IDs) with no `auth` parameter.
  3. Observe incoming `student.joined`, `student.updated`, `question.updated`, and `classroom.closed` frames.
- **Observed Output & Proven Invariant Failure**:
  > In the `bwrap` sandbox test (`scratch/proof.txt`), subscribing without `auth` to `classroom.1` returned `pusher_internal:subscription_succeeded`, and `EventDispatcher::dispatchSynchronously()` delivered `student.joined` (`student_name: Estudante Dummy Silva`, `score: 1`), `question.updated` (full `support_text`, `prompt`, and all 5 `options`), `classroom.closed` (`code: AVAL-DUMY`, `redirect_url: /sala/AVAL-DUMY/resultado`), and `student.updated` (`status`, `score`) to the unauthenticated client.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Split teacher monitoring and student broadcast channels: move `StudentJoinedClassroom` and `StudentAttemptUpdated` onto a dedicated `PrivateChannel('classroom.monitor.'.$classroomId)` authorized in `routes/channels.php` only for the owning professor or `super_admin`. For student-facing `QuestionUpdated` and `ClassroomClosed` events, replace predictable `classroom.{id}` names with a per-classroom unguessable broadcast channel identifier or authorize student subscriptions via a session-backed broadcasting auth endpoint.

**`app/Events/StudentAttemptUpdated.php`**:
```php
use Illuminate\Broadcasting\PrivateChannel;

public function broadcastOn(): array
{
    return [
        new PrivateChannel('classroom.monitor.'.$this->attempt->classroom_id),
    ];
}
```

**`app/Events/StudentJoinedClassroom.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 13. [MEDIUM] Cross-Assessment and Cross-Tenant Question Injection via Unscoped question_id in ClassroomPlayerController::saveAnswer

- **Fingerprint**: `student-player-state/cross-assessment-question-injection-score-inflation`
- **Overall Severity**: **MEDIUM** (Likelihood: `medium` — Requires tampering with JSON payload question_id and guessing sequential integer IDs and options A-E. | Impact: `high` — Allows cross-tenant question references, unbounded score/points inflation beyond assessment maximums, and corruption of professor analytics.)
- **Confidence**: `high` — Directly verified in source and reproduced end-to-end in the local sandbox (proof.txt TEST 2).

### Description & Root Cause
POST /sala/{code}/atividade/responder accepts any question_id existing in the global questions table—even questions belonging to other assessments or other teachers—persists a StudentAnswer row on the student's attempt, and inflates score, earnedPoints(), and AssessmentController::statistics() accuracy rates above 100%.

- **Root Cause**: ClassroomPlayerController::saveAnswer() validates question_id with global 'exists:questions,id' and loads Question::findOrFail($validated['question_id']) without verifying that the question is attached to $classroom->assessment, while StudentAttempt::recalculateScore(), StudentAttempt::earnedPoints(), and AssessmentController::statistics() aggregate all answers on the attempt without scoping to $assessment->questions.
- **Intended Behavior**: saveAnswer() must reject any question_id not attached to $classroom->assessment->questions (404/422), and score/statistics calculations should only count answers belonging to the assessment's questions.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:33` (`Route::post('/atividade/responder')`) — Routes student answer submissions to ClassroomPlayerController::saveAnswer.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:149` (`ClassroomPlayerController::saveAnswer`) — Validates question_id globally with 'exists:questions,id' and fetches Question::findOrFail() without checking $classroom->assessment->questions().
3. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:180` (`ClassroomPlayerController::saveAnswer`) — Persists StudentAnswer for arbitrary question_id and calls $attempt->recalculateScore() (line 195).
4. **[sink]** `app/Models/StudentAttempt.php:47` (`StudentAttempt::recalculateScore`) — Counts all correct StudentAnswer records on $this->answers()->get() and earnedPoints() falls back to 1.0 points for unmapped question_ids (line 86).

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:149`: Validation rule uses 'exists:questions,id' with no pivot check against assessment_question.
- `app/Models/StudentAttempt.php:86`: earnedPoints() uses ($pointsMap[$ans->question_id] ?? 1.0), awarding 1.0 point per injected question outside the assessment.
- `app/Http/Controllers/Professor/AssessmentController.php:197`: statistics() sums all $attempt->answers without filtering by $avaliacao->questions, allowing accuracy_rate > 100%.

### Conditions & Containment
- **data_state**: Requires an active classroom session and at least one other Question record in the database outside $classroom->assessment.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: An authenticated student session in Teacher A's 2-question classroom submits POST /sala/{code}/atividade/responder with question_id values belonging to Teacher B.
- **Inputs / Payloads**:
  - `POST /sala/AVAL-TEST/atividade/responder {"question_id":3,"answer":"D","reason":"Answer reason","markers":[],"time_spent":1000}`
  - `POST /sala/AVAL-TEST/atividade/responder {"question_id":4,"answer":"E","reason":"Answer reason","markers":[],"time_spent":1000}`
- **Bounded Instructions**:
  1. Join Teacher A's 2-question classroom AVAL-TEST via POST /sala/iniciar.
  2. Submit correct answers for the 2 assessment questions plus 2 foreign questions belonging to Teacher B (qid=3, qid=4) via POST /sala/AVAL-TEST/atividade/responder.
  3. Inspect $attempt->score, $attempt->earnedPoints(), and AssessmentController::statistics($assessmentA).
- **Observed Output & Proven Invariant Failure**:
  > All four saveAnswer requests returned HTTP 200 {"success":true}. Attempt2 recorded score=4/2 and earnedPoints=6/4 (max expected 2 and 4.0), and AssessmentController::statistics() reported accuracy_rate=125% for Classroom A.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Scope question_id validation in saveAnswer() to questions belonging to $classroom->assessment_id via assessment_question, and filter answers in StudentAttempt::recalculateScore(), StudentAttempt::earnedPoints(), and AssessmentController::statistics() to the assessment's question IDs.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
```php
$question = $classroom->assessment->questions()->where('questions.id', $validated['question_id'])->first();
if (! $question) {
    return response()->json(['error' => 'Questão não pertence a esta avaliação.'], 422);
}
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 14. [MEDIUM] Collective Review Disablement Bypass and Sequential Replay Limit Bypass in finishReview

- **Fingerprint**: `student-player-state/finishreview-disabled-and-replay-limit-bypass`
- **Overall Severity**: **MEDIUM** (Likelihood: `high` — Requires only standard student session cookies and sequential form POSTs while the classroom is active. | Impact: `medium` — Enables students to override disabled collective review and exceed the teacher-configured max_review_changes limit across any number of questions.)
- **Confidence**: `high` — Directly verified in source and reproduced for both bypass vectors in proof.txt TEST 4.

### Description & Root Cause
ClassroomPlayerController::finishReview() executes even when collective_review_enabled is false (explicitly skipping the max_review_changes guard), and when collective_review_enabled is true it only counts changes within the current POST payload, allowing sequential partial POST requests to exceed max_review_changes.

- **Root Cause**: Unlike showReview(), finishReview() does not check $classroom->collective_review_enabled before processing reviews and skips the limit check on line 321 when false; additionally, finishReview() does not check $attempt->status === 'reviewed' or compute cumulative changes across existing StudentAnswer rows.
- **Intended Behavior**: finishReview() must reject requests when collective_review_enabled is false or when the attempt has already been reviewed, and must enforce max_review_changes against the cumulative total of modified questions.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:36` (`Route::post('/revisao/concluir')`) — Routes collective review submissions to ClassroomPlayerController::finishReview.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:288` (`ClassroomPlayerController::finishReview`) — Never checks !$classroom->collective_review_enabled or $attempt->status === 'reviewed', and computes $changedCount only over $request->input('reviews', []) (lines 307-319).
3. **[sink]** `app/Http/Controllers/Student/ClassroomPlayerController.php:321` (`ClassroomPlayerController::finishReview`) — Guards limit with ($classroom->collective_review_enabled && $changedCount > $maxChanges) and updates StudentAnswer rows (lines 344-348) and $attempt->status = 'reviewed' (line 354).

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:258`: showReview() redirects when !$classroom->collective_review_enabled, whereas finishReview() omits that check.
- `app/Http/Controllers/Student/ClassroomPlayerController.php:321`: Limit check ($classroom->collective_review_enabled && $changedCount > $maxChanges) is skipped when collective_review_enabled is false and ignores prior partial reviews when true.

### Conditions & Containment
- **data_state**: Requires an active classroom session; replay bypass requires an assessment with more questions than max_review_changes.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: A student in an active classroom POSTs directly to /sala/{code}/revisao/concluir either when collective review is disabled or as multiple partial requests with one question per request.
- **Inputs / Payloads**:
  - `POST /sala/AVAL-NORE/revisao/concluir reviews[1][reviewed_answer]=B&reviews[2][reviewed_answer]=C`
  - `POST /sala/AVAL-TEST/revisao/concluir reviews[1][reviewed_answer]=B (followed by) POST /sala/AVAL-TEST/revisao/concluir reviews[2][reviewed_answer]=C`
- **Bounded Instructions**:
  1. In classroom AVAL-NORE (collective_review_enabled=false), answer Q1 and Q2 wrongly, submit attempt, then POST both corrections to /sala/AVAL-NORE/revisao/concluir.
  2. In classroom AVAL-TEST (collective_review_enabled=true, max_review_changes=1), answer Q1 and Q2 wrongly, submit attempt, then send two sequential POSTs to /sala/AVAL-TEST/revisao/concluir changing Q1 in the first and Q2 in the second.
- **Observed Output & Proven Invariant Failure**:
  > On AVAL-NORE, GET /revisao redirected to resultado, but POST /revisao/concluir returned 302 to resultado with attempt_status=reviewed and score=2. On AVAL-TEST (max=1), Replay 1 set score=1 and Replay 2 set score=2 with total_changed_in_db=2.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Abort or redirect in finishReview() if !$classroom->collective_review_enabled or $attempt->status !== 'submitted', and calculate total changed questions across all existing answers merged with the request payload.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
```php
if (! $classroom->collective_review_enabled) {
    return redirect()->route('estudante.resultado', ['code' => $classroom->code]);
}
if ($attempt->status === 'reviewed') {
    return redirect()->route('estudante.resultado', ['code' => $classroom->code])
        ->with('info', 'A revisão coletiva já foi concluída.');
}
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 15. [MEDIUM] Missing Input Validation in finishReview and Incomplete Nested Validation in saveAnswer Causing Unhandled 500s and Invalid Answer/Marker Storage

- **Fingerprint**: `student-player-state/finishreview-saveanswer-missing-input-validation`
- **Overall Severity**: **MEDIUM** (Likelihood: `medium` — Requires crafted form or JSON payloads from a student session in an active classroom. | Impact: `medium` — Causes unhandled HTTP 500 errors on malformed inputs, persists invalid non-option values (such as 'Z') into student_answers.reviewed_answer, and enables persistent HTTP 500 ViewException errors on student result rendering via nested marker arrays.)
- **Confidence**: `high` — Directly verified in ClassroomPlayerController.php and resultado.blade.php, and reproduced with exact exception classes in proof.txt TEST 5.

### Description & Root Cause
ClassroomPlayerController::finishReview() performs no request validation, crashing with HTTP 500 on non-array reviews or array reviewed_answer inputs and persisting arbitrary non-option characters (e.g., 'Z') into student_answers.reviewed_answer. Additionally, saveAnswer() calls strtoupper() on raw answer before validation (crashing on arrays) and validates markers only as ['nullable', 'array'], allowing non-array elements and nested arrays in markers[*][text] that crash student result rendering (resultado.blade.php) with HTTP 500 ViewException.

- **Root Cause**: finishReview() reads $request->input('reviews', []) directly without $request->validate() or 'in:A,B,C,D,E' checks; saveAnswer() transforms $request->input('answer') with strtoupper() at line 145 prior to validation and validates 'markers' only as ['nullable', 'array'] at line 152 without validating element keys or string types.
- **Intended Behavior**: Both endpoints must validate scalar and nested element types strictly before transformation or persistence, enforcing 'in:A,B,C,D,E' for answers and scalar string constraints for marker fields and review reflections.

### Ordered Trace
1. **[entrypoint]** `routes/web.php:33` (`Route::post('/atividade/responder') & Route::post('/revisao/concluir') (line 36)`) — Unauthenticated student routes dispatch POST /sala/{code}/atividade/responder (line 33) and POST /sala/{code}/revisao/concluir (line 36) without request validation middleware.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:145` (`ClassroomPlayerController::saveAnswer & finishReview (line 302)`) — saveAnswer() runs strtoupper($request->input('answer')) before validation (line 145) and validates markers as ['nullable', 'array'] without element rules (line 152, storing to initial_markers at line 188); finishReview() reads unvalidated $request->input('reviews', []) at line 302 and stores unvalidated reviewed_answer at line 345.
3. **[sink]** `resources/views/estudante/resultado.blade.php:121` (`estudante.resultado Blade view & StudentAnswer persistence`) — Passes stored nested marker arrays to TextHighlightService::applyHighlights() (lines 101, 106, 121), throwing HTTP 500 ViewException (Array to string conversion), alongside unhandled TypeError/ErrorException 500s and unvalidated DB storage ('Z') in finishReview().

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:145`: strtoupper($request->input('answer')) executes before $request->validate(); line 152 validates 'markers' as ['nullable', 'array'] with no markers.* element rules.
- `app/Http/Controllers/Student/ClassroomPlayerController.php:302`: finishReview() has no $request->validate() call and persists any string in reviewed_answer (line 345).
- `resources/views/estudante/resultado.blade.php:121`: Invokes TextHighlightService::applyHighlights($q->prompt, $markers, 'prompt') (and at lines 101/106 for support_title/support_text), throwing ViewException (Array to string conversion) when markers[*][text] is stored as an array.
- `resources/views/estudante/resultado.blade.php:139`: Passes $m['text'] ?? '' directly to Blade {{ }} (htmlspecialchars()), which cannot accept array values.

### Conditions & Containment
- **data_state**: Requires an active classroom and student session; triggering the persisted ViewException requires visiting resultado after saving nested array marker text.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: A student session sends malformed JSON or form payloads to /sala/{code}/atividade/responder or /sala/{code}/revisao/concluir.
- **Inputs / Payloads**:
  - `POST /sala/AVAL-TEST/revisao/concluir (reviews='bad-string')`
  - `POST /sala/AVAL-TEST/revisao/concluir (reviews[1][reviewed_answer]=['B'])`
  - `POST /sala/AVAL-TEST/revisao/concluir (reviews[1][reviewed_answer]='Z')`
  - `POST /sala/AVAL-TEST/atividade/responder {"question_id":1,"answer":["A"],"reason":"x"}`
  - `POST /sala/AVAL-TEST/atividade/responder {"question_id":1,"answer":"A","reason":"x","markers":[{"kind":"command","text":["nested"]}]}`
- **Bounded Instructions**:
  1. Send malformed reviews='bad-string', reviewed_answer=['B'], and reviewed_answer='Z' to POST /sala/AVAL-TEST/revisao/concluir.
  2. Send answer=['A'] and markers=[{'kind':'command','text':['nested']}] to POST /sala/AVAL-TEST/atividade/responder, then GET /sala/AVAL-TEST/resultado.
- **Observed Output & Proven Invariant Failure**:
  > finishReview returned HTTP 500 ErrorException on reviews='bad-string', HTTP 500 TypeError on reviewed_answer=['B'], and HTTP 302 storing reviewed_answer='Z' in DB (reviews[qid]='not-an-array' returned 302 due to isset/??). saveAnswer returned HTTP 500 TypeError on answer=['A'], HTTP 200 on markers=['not-an-array'], and HTTP 200 on markers[0][text]=['nested'] which subsequently caused HTTP 500 ViewException (Array to string conversion) on GET /sala/AVAL-TEST/resultado.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Add strict $request->validate() rules in finishReview() requiring reviews to be an array of arrays with reviewed_answer in A..E and string review_reflection; in saveAnswer(), guard strtoupper() with is_string() and validate answer as string plus nested markers.* schema rules.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
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

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
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

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

## 16. [MEDIUM] Post-Submission and Post-Review Answer Tampering via Missing Attempt Status Guard in saveAnswer

- **Fingerprint**: `student-player-state/saveanswer-post-submission-state-bypass`
- **Overall Severity**: **MEDIUM** (Likelihood: `high` — Any student can send a POST to /sala/{code}/atividade/responder with their existing session while the classroom is open. | Impact: `medium` — Subverts the two-stage individual-vs-collective evaluation model, bypasses max_review_changes, and leaves reviewed_answer inconsistent with score and is_correct_final.)
- **Confidence**: `high` — Directly verified in source and reproduced for both 'submitted' and 'reviewed' states in proof.txt TEST 3.

### Description & Root Cause
ClassroomPlayerController::saveAnswer() allows a student to overwrite initial_answer, initial_reason, initial_markers, is_correct_initial, is_correct_final, and score even after submitAttempt() (status='submitted') or finishReview() (status='reviewed'), bypassing max_review_changes and desynchronizing reviewed_answer from is_correct_final.

- **Root Cause**: ClassroomPlayerController::saveAnswer() checks only whether the StudentAttempt exists and $classroom->is_active is true, omitting any guard requiring $attempt->status === 'in_progress'.
- **Intended Behavior**: saveAnswer() must reject answer modifications once the student has finalized initial submission (status !== 'in_progress').

### Ordered Trace
1. **[entrypoint]** `routes/web.php:33` (`Route::post('/atividade/responder')`) — Exposes ClassroomPlayerController::saveAnswer to the student session.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:130` (`ClassroomPlayerController::saveAnswer`) — Checks !$attempt and !$classroom->is_active (lines 132-142) without checking $attempt->status.
3. **[sink]** `app/Http/Controllers/Student/ClassroomPlayerController.php:180` (`ClassroomPlayerController::saveAnswer`) — Calls StudentAnswer::updateOrCreate overwriting initial_answer, is_correct_initial, and is_correct_final, then calls $attempt->recalculateScore() (line 195).

### Supporting Source Evidence
- `app/Http/Controllers/Student/ClassroomPlayerController.php:132`: Only verifies $attempt presence and $classroom->is_active; no check for $attempt->status === 'in_progress'.
- `app/Http/Controllers/Student/ClassroomPlayerController.php:185`: Overwrites initial_answer, is_correct_initial, and is_correct_final regardless of prior submission or review.

### Conditions & Containment
- **data_state**: Requires a classroom that remains active (is_active = true) after the student calls submitAttempt or finishReview.

### Target-Native Bounded Reproduction (Dummy Fixture / Local Sandbox)
- **Lower-Trust / Attacker Principal**: A student submits their attempt (or completes collective review) and replays POST /sala/{code}/atividade/responder while the classroom is still open.
- **Inputs / Payloads**:
  - `POST /sala/AVAL-TEST/atividade/concluir`
  - `POST /sala/AVAL-TEST/atividade/responder {"question_id":1,"answer":"B","reason":"Overwritten after submit","markers":[]}`
- **Bounded Instructions**:
  1. Join classroom AVAL-TEST, answer Q1 and Q2 wrongly ('A'), and call POST /sala/AVAL-TEST/atividade/concluir (status='submitted').
  2. Replay POST /sala/AVAL-TEST/atividade/responder for Q1 with answer='B', then call finishReview changing Q2 to 'C'.
  3. In a separate attempt, call submitAttempt and finishReview (status='reviewed', score=0), then replay POST /sala/AVAL-TEST/atividade/responder for Q1 with answer='B'.
- **Observed Output & Proven Invariant Failure**:
  > While status='submitted', saveAnswer returned HTTP 200, updated q1 initial_answer='B' and is_correct_final=true (score=1), enabling finishReview to change Q2 as well for score=2 despite max_review_changes=1. While status='reviewed', saveAnswer returned HTTP 200, updated initial_answer='B', is_correct_final=true, and score=1 while reviewed_answer remained 'A'.

### Source-Level Remediation & Regression Guidance
- **Strategy**: Enforce $attempt->status === 'in_progress' in saveAnswer() (and guard submitAttempt() against already-submitted/reviewed attempts), returning HTTP 422 when the attempt has already been submitted.

**`app/Http/Controllers/Student/ClassroomPlayerController.php`**:
```php
if ($attempt->status !== 'in_progress') {
    return response()->json([
        'error' => 'As respostas iniciais já foram enviadas e não podem mais ser alteradas.',
    ], 422);
}
```

- **Regression Test Case**: Add a Pest feature test reproducing the exact dummy principal/input sequence above and asserting the enforced invariant rejects or prevents the unsafe state/disclosure (e.g., HTTP 401/403/422, private channel auth rejection, or exact score/query bound).

---

