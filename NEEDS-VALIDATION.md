# Unresolved Leads Requiring Validation — Corujinha (`run-1`)

This document details all **7** source-grounded `needs_validation` leads. None of these items are confirmed vulnerabilities and **no severity rating is assigned**. Each lead lists its verified source trace, evidence, exact unresolved blocker(s), affected boundary, and each applicable bounded local or owner-observed deployment checks.

---

## 1. Password reset notification generates reset URL from incoming Host header without TrustHosts or forced root URL

- **Fingerprint**: `auth/password-reset-host-header-poisoning`
- **Affected Boundary**: `routes/web.php:60 -> app/Notifications/ResetPasswordNotification.php:35`
- **Summary**: ForgotPasswordController::sendResetLinkEmail() invokes Laravel's Password::sendResetLink(), which triggers User::sendPasswordResetNotification() and ResetPasswordNotification::toMail(). ResetPasswordNotification::toMail() builds the reset URL with route('password.reset', ...), which uses the incoming request's Host header because neither TrustHosts middleware (bootstrap/app.php) nor URL::forceRootUrl() (AppServiceProvider::boot()) is configured. Locally, sending POST /esqueci-minha-senha with Host: evil.example causes the queued/dispatched email to contain http://evil.example/redefinir-senha/<token>. Because production deploys behind CloudPanel Nginx, exploitability depends on whether Nginx forwards unrecognized Host headers to PHP-FPM.
- **Claimed Root Cause**: ResetPasswordNotification::toMail() relies on route('password.reset', ...) without TrustHosts middleware or URL::forceRootUrl() pinning APP_URL, trusting the HTTP Host header if upstream Nginx forwards it.

### Source Trace
1. **[entrypoint]** `routes/web.php:60` (`guest route group`) — Routes POST /esqueci-minha-senha to ForgotPasswordController::sendResetLinkEmail.
2. **[propagation]** `app/Http/Controllers/Auth/ForgotPasswordController.php:33` (`ForgotPasswordController::sendResetLinkEmail`) — Calls Password::sendResetLink($request->only('email')), invoking User::sendPasswordResetNotification($token) at app/Models/User.php:124-127.
3. **[sink]** `app/Notifications/ResetPasswordNotification.php:35` (`ResetPasswordNotification::toMail`) — Generates $resetUrl via route('password.reset', ...) using the request host when TrustHosts and URL::forceRootUrl() are absent (verified locally in proof.txt TEST 4: actionUrl=http://evil.example/redefinir-senha/...).

### Verified Source Evidence
- `app/Notifications/ResetPasswordNotification.php:35`: Calls route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]) without pinning APP_URL.
- `bootstrap/app.php:16`: withMiddleware() does not configure $middleware->trustHosts().
- `app/Providers/AppServiceProvider.php:20`: boot() is empty and does not call URL::forceRootUrl().
- `.github/workflows/deploy.yml:51`: Deploys to CloudPanel VPS (/home/bissoli-corujinha/htdocs/corujinha.bissoli.tech); Nginx vhost config is outside the repository.

### Exact Unresolved Blocker(s)
- Live CloudPanel Nginx virtual-host configuration (server_name matching, presence of a catch-all default_server block, and fastcgi_param HTTP_HOST / SERVER_NAME handling) is outside the repository and unobserved.

### Safe Resolution Plan
- **Bounded Local Check**: Already verified in bwrap sandbox (proof.txt TEST 4): POST /esqueci-minha-senha with Host: evil.example produced actionUrl=http://evil.example/redefinir-senha/<token>?email=prof.totp%40example.com.
- **Owner-Observed Deployment Check**: Inspect the live CloudPanel Nginx vhost for corujinha.bissoli.tech (and any default_server block on ports 80/443) or send a single bounded HEAD/GET request with a benign Host header to confirm whether Nginx rejects unknown Host headers before PHP-FPM.

---

## 2. POST /login/totp lacks application-level rate limiting or attempt lockout across failed TOTP challenges

- **Fingerprint**: `auth/totp-login-unlimited-brute-force-bypass`
- **Affected Boundary**: `routes/web.php:53 -> app/Http/Controllers/Auth/AuthController.php:104`
- **Summary**: The second-factor verification endpoint POST /login/totp (AuthController::verifyTotp) does not apply Laravel throttle middleware, per-session attempt counters, or account/challenge lockout. In local sandbox execution against Laravel's HTTP kernel, 20 consecutive incorrect 6-digit codes returned HTTP 302 validation redirects while keeping totp_auth_user_id in the session, and the 21st request with the valid code authenticated the user and granted HTTP 200 access to /professor/salas. However, the application deploys behind CloudPanel Nginx (corujinha.bissoli.tech), whose reverse-proxy rate-limiting configuration is outside the repository.
- **Claimed Root Cause**: Route::post('/login/totp', [AuthController::class, 'verifyTotp']) is registered with only the guest middleware and no throttle middleware or RateLimiter, and AuthController::verifyTotp() never tracks failed attempts or clears session('totp_auth_user_id') when TotpService::verify() returns false across a +/-1 30s time-slice window.

### Source Trace
1. **[entrypoint]** `routes/web.php:53` (`POST /login/totp route`) — Registers POST /login/totp -> AuthController::verifyTotp inside the guest middleware group with no throttle middleware.
2. **[propagation]** `app/Http/Controllers/Auth/AuthController.php:90` (`AuthController::verifyTotp`) — Reads totp_auth_user_id from the session and keeps it intact across failed attempts.
3. **[sink]** `app/Http/Controllers/Auth/AuthController.php:104` (`AuthController::verifyTotp`) — Calls TotpService::verify((string) $user->totp_secret, $request->input('code')) and redirects back on failure (lines 105-107) without throttling, locking out, or invalidating the pre-auth session.

### Verified Source Evidence
- `routes/web.php:53`: POST /login/totp has no rate-limiting middleware attached.
- `app/Http/Controllers/Auth/AuthController.php:104`: Failed TotpService::verify() returns back()->withErrors(...) without incrementing an attempt counter or clearing totp_auth_user_id.
- `app/Services/TotpService.php:26`: TotpService::verify() accepts a +/- 1 slice window (3 valid 6-digit codes per 90s span) with no rate or attempt tracking.
- `app/Providers/AppServiceProvider.php:20`: AppServiceProvider::boot() defines no RateLimiter for login or TOTP verification.
- `.github/workflows/deploy.yml:51`: Production deployment targets CloudPanel (/home/bissoli-corujinha/htdocs/corujinha.bissoli.tech), whose Nginx vhost is not in the repository.

### Exact Unresolved Blocker(s)
- CloudPanel Nginx vhost and any edge/WAF/Fail2ban rate-limiting rules (e.g., limit_req_zone / limit_req on /login/totp) for corujinha.bissoli.tech are outside the repository and cannot be verified from source.

### Safe Resolution Plan
- **Bounded Local Check**: Already verified in bwrap sandbox (proof.txt): 20 consecutive wrong TOTP submissions to POST /login/totp all returned HTTP 302 without 429 or clearing totp_auth_user_id, and attempt 21 with the valid code redirected to /professor/salas (HTTP 200).
- **Owner-Observed Deployment Check**: Inspect the CloudPanel Nginx vhost configuration and WAF/Fail2ban rules for corujinha.bissoli.tech to check whether POST /login/totp is enforced with strict per-IP/per-session rate limiting (e.g., 5 requests/minute), or send a bounded owner-authorized burst of >15 invalid POST /login/totp requests against a staging/production instance sharing the same proxy config to observe whether HTTP 429 is returned.

---

## 3. Unbounded eager-loading and N+1 lazy-loading across ClassroomController::show, devolutiva, and AssessmentController::statistics

- **Fingerprint**: `availability/unbounded-attempts-eager-load-n-plus-one`
- **Affected Boundary**: `routes/web.php:29 -> app/Http/Controllers/Professor/AssessmentController.php:186`
- **Summary**: ClassroomController::show() eager-loads all studentAttempts.answers.question onto $sala despite calling paginate(20) on a separate query; ClassroomController::devolutiva() eager-loads studentAttempts.answers onto $sala and then executes a second un-eager-loaded studentAttempts()->orderBy('student_name')->get() query whose loop triggers N+1 student_answers queries; and AssessmentController::statistics() eager-loads all classrooms.studentAttempts.answers into PHP memory without SQL aggregation. While DB::enableQueryLog() locally proved the N+1 query growth and full-table memory hydration across 5 vs 25 attempts, exploiting this for service DoS requires sustained unauthenticated POST /sala/iniciar volume, whose feasibility and OOM/timeout thresholds depend on external Nginx rate limiting and PHP runtime limits.
- **Claimed Root Cause**: ClassroomController::show(), ClassroomController::devolutiva(), and AssessmentController::statistics() load unbounded student_attempts and student_answers collections into PHP memory (with devolutiva() additionally triggering N+1 lazy queries), while POST /sala/iniciar has no per-classroom attempt limit or application rate limit.

### Source Trace
1. **[entrypoint]** `routes/web.php:29` (`POST /sala/iniciar`) — Unauthenticated student join endpoint creates StudentAttempt rows with no application-level rate limit or per-classroom cap.
2. **[propagation]** `app/Http/Controllers/Professor/ClassroomController.php:102` (`ClassroomController::show`) — Eager-loads all studentAttempts.answers.question into memory even though line 105 paginates attempts at 20 per page.
3. **[propagation]** `app/Http/Controllers/Professor/ClassroomController.php:218` (`ClassroomController::devolutiva`) — Accesses $attempt->answers on the second un-eager-loaded collection from line 208, issuing 1 SELECT FROM student_answers query per attempt after already eager-loading studentAttempts.answers at line 204.
4. **[sink]** `app/Http/Controllers/Professor/AssessmentController.php:186` (`AssessmentController::statistics`) — Eager-loads all classrooms.studentAttempts.answers across every classroom of an assessment into PHP memory.

### Verified Source Evidence
- `app/Http/Controllers/Professor/ClassroomController.php:105`: For N=25 attempts (5 questions each), show() ran 9 queries and returned 20 paginated items while still hydrating all 25 attempts onto $classroom->studentAttempts.
- `app/Http/Controllers/Professor/ClassroomController.php:208`: DB::enableQueryLog() on devolutiva() grew linearly with N: N=5 executed 12 total queries (6 SELECTs on student_answers); N=25 executed 32 total queries (26 SELECTs on student_answers).
- `app/Http/Controllers/Professor/AssessmentController.php:186`: statistics() ran 5 queries and hydrated all StudentAnswer models in memory (25 for N=5; 125 for N=25).

### Exact Unresolved Blocker(s)
- Turning linear O(N) attempt accumulation into teacher-route OOM or request timeout requires sustained POST /sala/iniciar (and saveAnswer) request volume whose feasibility depends on CloudPanel/Nginx reverse-proxy rate limiting or WAF controls outside the repository.
- Exact failure thresholds (number of attempts/answers required to exhaust memory or timeout) depend on deployed PHP memory_limit, max_execution_time, and MariaDB query/latency configuration outside the repository.

### Safe Resolution Plan
- **Owner-Observed Deployment Check**: Owner should inspect CloudPanel Nginx configuration for rate limits on /sala/iniciar and /sala/*/atividade/responder, and check production PHP memory_limit, max_execution_time, and PHP-FPM request_terminate_timeout against expected maximum class/assessment attempt counts.

---

## 4. Missing unique constraint on student_answers and non-atomic updateOrCreate in saveAnswer allow duplicate answer rows to inflate scores if concurrent requests overlap

- **Fingerprint**: `chain:concurrent-saveAnswer-duplicate-student-answers-score-inflation`
- **Affected Boundary**: `routes/web.php:33 -> app/Http/Controllers/Professor/AssessmentController.php:197`
- **Summary**: The student_answers table lacks a unique index on (student_attempt_id, question_id) and ClassroomPlayerController::saveAnswer() uses StudentAnswer::updateOrCreate() without a database transaction or row/request lock. Local sandbox execution proved that duplicate rows for the same (student_attempt_id, question_id) are accepted by the schema and summed by StudentAttempt::recalculateScore(), StudentAttempt::earnedPoints(), and AssessmentController::statistics(), crossing the unauthenticated-student-to-assessment-integrity boundary to inflate scores and classroom accuracy rates past 100%. End-to-end concurrent HTTP exploitation was not triggered in the single-process SQLite :memory: harness and depends on deployed PHP-FPM worker concurrency, MariaDB transaction/locking behavior, and upstream CloudPanel Nginx concurrency/rate-limiting rules outside the repository.
- **Claimed Root Cause**: student_answers lacks a composite unique constraint on (student_attempt_id, question_id), ClassroomPlayerController::saveAnswer() relies on non-atomic updateOrCreate() (SELECT then INSERT) with no transaction or lock, and scoring methods (recalculateScore, earnedPoints, AssessmentController::statistics) aggregate all rows in student_answers without deduplicating by question_id.

### Source Trace
1. **[entrypoint]** `routes/web.php:33` (`POST /sala/{code}/atividade/responder`) — Student answer submission route has no concurrency lock, session blocking, or WithoutOverlapping middleware.
2. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:180` (`ClassroomPlayerController::saveAnswer`) — Calls StudentAnswer::updateOrCreate(['student_attempt_id' => $attempt->id, 'question_id' => $question->id], ...) without a database transaction, row lock, or unique DB key.
3. **[propagation]** `database/migrations/2026_11_10_152104_create_student_answers_table.php:17` (`student_answers migration`) — Defines student_attempt_id and question_id foreign keys without a composite unique index.
4. **[propagation]** `app/Models/StudentAttempt.php:49` (`StudentAttempt::recalculateScore`) — Queries $this->answers()->get() and counts every correct StudentAnswer row without deduplicating by question_id.
5. **[propagation]** `app/Models/StudentAttempt.php:77` (`StudentAttempt::earnedPoints`) — Sums points across all rows in $this->answers without deduplicating by question_id.
6. **[sink]** `app/Http/Controllers/Professor/AssessmentController.php:197` (`AssessmentController::statistics`) — Sums all correct StudentAnswer rows per attempt into total_correct, avg_correct, and accuracy_rate.

### Verified Source Evidence
- `database/migrations/2026_11_10_152104_create_student_answers_table.php:16`: student_answers schema defines foreign keys on student_attempt_id and question_id with no unique index; PRAGMA index_list('student_answers') returned [] and two rows for (attempt=2, question=2) were accepted.
- `app/Http/Controllers/Student/ClassroomPlayerController.php:180`: StudentAnswer::updateOrCreate performs a non-atomic SELECT followed by INSERT without transaction or lock.
- `app/Models/StudentAttempt.php:51`: With two duplicate correct rows on a 1-question (2.5-point) assessment, recalculateScore() returned 2, earnedPoints() returned 5.0, and totalPossiblePoints() was 2.5.
- `app/Http/Controllers/Professor/AssessmentController.php:197`: AssessmentController::statistics() reported total_correct=2, avg_correct=2, and accuracy_rate=200% on the duplicate-row fixture.

### Exact Unresolved Blocker(s)
- Deployed PHP-FPM worker concurrency (pm.max_children > 1) and upstream CloudPanel Nginx request concurrency/rate-limiting rules governing whether simultaneous POST /sala/{code}/atividade/responder requests overlap in production are outside the repository.
- Production MariaDB engine/transaction_isolation and locking behavior on concurrent StudentAnswer::updateOrCreate() calls (as opposed to local SQLite) are outside the repository.

### Safe Resolution Plan
- **Bounded Local Check**: Run a bounded two-process HTTP concurrency test against a disposable local MariaDB/MySQL instance (or file-backed database with parallel workers) issuing two simultaneous POST /sala/{code}/atividade/responder requests for the same (attempt_id, question_id) and assert whether two student_answers rows are inserted.
- **Owner-Observed Deployment Check**: Owner should inspect production PHP-FPM pm.max_children, MariaDB transaction_isolation and student_answers indexes, and CloudPanel Nginx concurrency/rate-limiting rules, and query student_answers for duplicate (student_attempt_id, question_id) rows.

---

## 5. Committed `.env.example` Reverb Credentials Match Local `.env.production` and Enable Pusher HTTP API Request Forgery If Reused and Exposed

- **Fingerprint**: `deploy.env-example-reverb-secret-reuse`
- **Affected Boundary**: `.env.example:70 -> vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php:115`
- **Summary**: `.env.example` commits non-empty `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` values. Boolean comparison inside the `bwrap` sandbox confirms that the gitignored `.env` and `.env.production` files present in the workspace use the exact same `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` as `.env.example`. Knowing `REVERB_APP_ID` and `REVERB_APP_SECRET` allows computing valid HMAC-SHA256 `auth_signature` tokens accepted by Reverb's Pusher HTTP API (`POST /apps/{appId}/events`, `GET /apps/{appId}/channels`, etc.) to inject arbitrary spoofed WebSocket events or inspect active channels/connections. However, production deployment via `.github/workflows/deploy.yml` decrypts `.env.production.encrypted` using the unobserved GitHub secret `LARAVEL_ENV_ENCRYPTION_KEY`, and CloudPanel Nginx routing/firewalling for `/apps/*` and `REVERB_SERVER_PORT` (`8080`/`8081`) is outside the repository.
- **Claimed Root Cause**: `.env.example` ships concrete non-empty `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` literals instead of blank placeholders, and Reverb's HTTP API controllers (`Controller::verifySignature`) rely solely on `hash_hmac('sha256', ..., $this->application->secret())` to authenticate server-side event publication and channel inspection requests.

### Source Trace
1. **[entrypoint]** `.env.example:70` (`.env.example`) — Commits non-empty `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` values (lines 70-72) in the tracked git repository.
2. **[propagation]** `.github/workflows/deploy.yml:60` (`deploy job`) — Decrypts tracked `.env.production.encrypted` into `.env` using `LARAVEL_ENV_ENCRYPTION_KEY`, falling back to `.env.production` if present (lines 60-66), and restarts Reverb (line 84).
3. **[propagation]** `config/reverb.php:76` (`reverb.apps.apps.0`) — Configures Reverb's active application `key`, `secret`, and `app_id` directly from `REVERB_APP_KEY`, `REVERB_APP_SECRET`, and `REVERB_APP_ID`.
4. **[sink]** `vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php:115` (`Controller::verifySignature`) — Authenticates `POST /apps/{appId}/events` and `GET /apps/{appId}/channels` strictly via HMAC-SHA256 using `$this->application->secret()` (`EventsController.php:25`, `Factory.php:99-105`).

### Verified Source Evidence
- `.env.example:70`: Contains non-empty `REVERB_APP_ID` (length 6), `REVERB_APP_KEY` (length 20), and `REVERB_APP_SECRET` (length 20) at lines 70-72.
- `.gitignore:5`: `.env` and `.env.production` are gitignored; `.env.production.encrypted` is tracked in git.
- `.github/workflows/deploy.yml:60`: Production deploy decrypts `.env.production.encrypted` with GitHub Actions secret `LARAVEL_ENV_ENCRYPTION_KEY` (lines 22, 60-62), making live deployed secret values unobservable without the key.
- `vendor/laravel/reverb/src/Protocols/Pusher/Http/Controllers/Controller.php:115`: Verifies Pusher HTTP API requests with `hash_hmac('sha256', $signature, $this->application->secret())` and `hash_equals`; local `bwrap` check confirmed `EventsController::verify()` accepts a forged `POST /apps/{appId}/events` request signed with `.env.example` credentials.

### Exact Unresolved Blocker(s)
- `.env.production.encrypted` cannot be decrypted without the repository owner's `LARAVEL_ENV_ENCRYPTION_KEY` secret, so whether the deployed `.env` on the VPS matches the workspace's gitignored `.env.production` (which `hash_equals` confirmed matches `.env.example`) is unverified.
- CloudPanel Nginx vhost and host firewall rules are outside the repository, so it is unknown whether Reverb's HTTP API paths (`/apps/{appId}/events`, `/apps/{appId}/channels`) or `REVERB_SERVER_PORT` (`8080`/`8081`) are proxied/exposed to untrusted networks or restricted to localhost.

### Safe Resolution Plan
- **Bounded Local Check**: Already verified in `bwrap` (`scratch/proof.txt`): `.env.example` has non-empty `REVERB_APP_ID`/`REVERB_APP_KEY`/`REVERB_APP_SECRET`, gitignored workspace `.env` and `.env.production` match `.env.example` on all three fields via `hash_equals`, and `EventsController::verify()` accepts a forged HMAC-SHA256 request signed with those credentials.
- **Owner-Observed Deployment Check**: Owner must: (1) check the live VPS `.env` (or decrypt `.env.production.encrypted` with `LARAVEL_ENV_ENCRYPTION_KEY`) to confirm whether `REVERB_APP_ID`/`REVERB_APP_KEY`/`REVERB_APP_SECRET` match `.env.example`; and (2) inspect the CloudPanel Nginx vhost and firewall to verify whether `/apps/*` or `REVERB_SERVER_PORT` (`8081`/`8080`) is reachable from the internet instead of only `/app/*` WebSocket upgrades.

---

## 6. Missing TrustProxies and URL::forceScheme('https') causes http:// URL generation when TLS terminates at an upstream proxy

- **Fingerprint**: `deploy.missing-trust-proxies-and-https-scheme`
- **Affected Boundary**: `bootstrap/app.php:16 -> app/Notifications/ResetPasswordNotification.php:35`
- **Summary**: Neither bootstrap/app.php configures $middleware->trustProxies() nor AppServiceProvider::boot() configures URL::forceScheme('https'), even though .env.production sets APP_URL=https://corujinha.bissoli.tech and SESSION_SECURE_COOKIE=true. In the bwrap sandbox, when a simulated proxied request arrives with HTTP_X_FORWARDED_PROTO='https' and HTTPS unset, Laravel treats the request as insecure ($request->isSecure()=false, getScheme()='http') and route('password.reset', ...) inside ResetPasswordNotification::toMail() emits http://corujinha.bissoli.tech/redefinir-senha/<token>. However, whether live CloudPanel Nginx terminates TLS directly and passes FastCGI HTTPS=on to PHP-FPM, or sits behind an upstream TLS proxy passing X-Forwarded-Proto over HTTP, is outside the repository.
- **Claimed Root Cause**: Absence of $middleware->trustProxies(...) in bootstrap/app.php and URL::forceScheme('https') in AppServiceProvider::boot() causes Laravel to ignore X-Forwarded-Proto: https and generate http:// URLs when PHP-FPM does not receive HTTPS=on.

### Source Trace
1. **[entrypoint]** `bootstrap/app.php:16` (`Application::configure()->withMiddleware`) — Configures application middleware without calling $middleware->trustProxies(...).
2. **[propagation]** `app/Providers/AppServiceProvider.php:20` (`AppServiceProvider::boot`) — Empty boot() method without URL::forceScheme('https') despite APP_URL=https://corujinha.bissoli.tech in .env.production:5.
3. **[sink]** `app/Notifications/ResetPasswordNotification.php:35` (`ResetPasswordNotification::toMail`) — Calls route('password.reset', ...), emitting http://corujinha.bissoli.tech/redefinir-senha/... when HTTP_X_FORWARDED_PROTO='https' and HTTPS is unset (proof.txt TEST 5).

### Verified Source Evidence
- `bootstrap/app.php:16`: No trustProxies() call in withMiddleware().
- `app/Providers/AppServiceProvider.php:20`: No URL::forceScheme('https') call in boot().
- `.env.production:5`: Sets APP_URL=https://corujinha.bissoli.tech and SESSION_SECURE_COOKIE=true (line 36).
- `.github/workflows/deploy.yml:1`: Targets CloudPanel VPS deployment; Nginx FastCGI HTTPS parameter and upstream CDN/reverse-proxy topology are not in the repository.

### Exact Unresolved Blocker(s)
- Live CloudPanel Nginx FastCGI configuration (whether fastcgi_param HTTPS on is set directly by Nginx on port 443) and whether an upstream reverse proxy/CDN terminates TLS and forwards HTTP with X-Forwarded-Proto: https are outside the repository.

### Safe Resolution Plan
- **Bounded Local Check**: Already verified in bwrap sandbox (proof.txt TEST 5): with HTTP_X_FORWARDED_PROTO='https' and HTTPS unset, $request->isSecure() was false, getScheme() was 'http', and ResetPasswordNotification::toMail() generated actionUrl=http://corujinha.bissoli.tech/redefinir-senha/<token>?email=prof.totp%40example.com.
- **Owner-Observed Deployment Check**: Check the CloudPanel Nginx vhost and fastcgi_params on the VPS (and whether Cloudflare/upstream proxy forwards over HTTP vs HTTPS) to verify whether PHP-FPM receives HTTPS=on or only X-Forwarded-Proto: https.

---

## 7. Low-Entropy Classroom Codes and Unthrottled Student Join and Player Endpoints

- **Fingerprint**: `student-player-state/classroom-code-enumeration-and-unthrottled-join`
- **Affected Boundary**: `routes/web.php:29 -> app/Models/Classroom.php:84`
- **Summary**: Classroom::generateUniqueCode() generates codes with prefix 'AVAL-' plus 4 uppercase alphanumeric characters (~20.68 bits, 36^4 = 1,679,616 total combinations, letter-biased by strtoupper(Str::random(4))), and neither POST /sala/iniciar nor GET /sala/{code}/atividade defines any in-repo rate-limiting middleware. Because CloudPanel Nginx reverse-proxy rate-limiting configuration is outside the repository, practical exploitability over the production network requires deployment validation.
- **Claimed Root Cause**: Classroom::generateUniqueCode() uses a small 4-character suffix space and routes/web.php defines no throttle middleware on estudante.iniciar or estudante.player, while GET /sala/{code}/atividade acts as an existence oracle (HTTP 404 vs 302).

### Source Trace
1. **[entrypoint]** `routes/web.php:29` (`Route::post('/iniciar')`) — Student join endpoint registered without throttle middleware.
2. **[propagation]** `routes/web.php:32` (`Route::get('/atividade')`) — Student player endpoint registered without throttle middleware.
3. **[propagation]** `app/Http/Controllers/Student/ClassroomPlayerController.php:81` (`ClassroomPlayerController::player`) — Uses Classroom::where('code', strtoupper($code))->firstOrFail(), returning 404 for nonexistent codes and 302 for valid codes even without a session.
4. **[sink]** `app/Models/Classroom.php:84` (`Classroom::generateUniqueCode`) — Generates $prefix.'-'.strtoupper(Str::random(4)), producing 36^4 = 1,679,616 possible suffixes.

### Verified Source Evidence
- `app/Models/Classroom.php:84`: strtoupper(Str::random(4)) limits default classroom codes to 4 uppercase alphanumeric characters (letter-biased over 36^4 space).
- `routes/web.php:29`: No throttle middleware is attached to POST /sala/iniciar or GET /sala/{code}/atividade (verified 15 rapid requests without HTTP 429 in proof.txt TEST 6).
- `bootstrap/app.php:16`: Global middleware configuration defines no rate limiting for web or student routes.
- `.github/workflows/deploy.yml:51`: Deploys to CloudPanel Nginx host (corujinha.bissoli.tech) whose vhost and proxy rate-limiting rules are external to the repository.

### Exact Unresolved Blocker(s)
- CloudPanel Nginx vhost configuration and any reverse-proxy / WAF rate limiting (e.g., limit_req_zone or Cloudflare throttling) for corujinha.bissoli.tech are not stored in this repository, preventing verification of whether high-volume enumeration across 36^4 codes is blocked at the edge.

### Safe Resolution Plan
- **Bounded Local Check**: Verified in proof.txt TEST 6 that Laravel applies zero throttling in-repo: 15 consecutive GET /sala/AVAL-000X/atividade requests returned 404, GET /sala/AVAL-TEST/atividade returned 302, and 15 consecutive POST /sala/iniciar requests returned 302 with no HTTP 429 responses.
- **Owner-Observed Deployment Check**: Inspect the CloudPanel Nginx vhost and any upstream CDN/WAF rules on corujinha.bissoli.tech for rate limiting (limit_req) on /sala/iniciar and /sala/*/atividade; if absent or permissive, confirm high-rate enumeration is unmitigated.

---

