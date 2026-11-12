<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class PasskeyService
{
    /**
     * Codifica bytes binários em Base64URL (sem padding).
     */
    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodifica uma string Base64URL (ou Base64 padrão) para bytes binários.
     */
    public function base64UrlDecode(string $data): string
    {
        $normalized = strtr($data, '-_', '+/');
        $remainder = strlen($normalized) % 4;
        if ($remainder > 0) {
            $normalized .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($normalized, true);
        if ($decoded === false) {
            throw new InvalidArgumentException('Dados Base64URL inválidos.');
        }

        return $decoded;
    }

    /**
     * Retorna o Relying Party ID (domínio canônico configurado em APP_URL).
     */
    public function rpId(Request $request): string
    {
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($configuredHost) && $configuredHost !== '') {
            return strtolower($configuredHost);
        }

        return strtolower($request->getHost());
    }

    /**
     * Gera as opções WebAuthn para registro de uma nova Passkey.
     *
     * @return array<string, mixed>
     */
    public function generateRegistrationOptions(User $user, Request $request): array
    {
        $challenge = $this->base64UrlEncode(random_bytes(32));
        $rpId = $this->rpId($request);

        session([
            'webauthn_register_challenge' => $challenge,
            'webauthn_register_rp_id' => $rpId,
            'webauthn_register_uv' => 'required',
        ]);

        $existing = $user->webauthnCredentials()
            ->pluck('credential_id')
            ->map(fn (string $id): array => [
                'type' => 'public-key',
                'id' => $id,
            ])
            ->values()
            ->all();

        $userHandleSource = $user->uuid ?: (string) $user->id;

        return [
            'challenge' => $challenge,
            'rp' => [
                'name' => config('app.name', 'Corujinha'),
                'id' => $rpId,
            ],
            'user' => [
                'id' => $this->base64UrlEncode($userHandleSource),
                'name' => $user->email,
                'displayName' => $user->name,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'none',
            'excludeCredentials' => $existing,
            'authenticatorSelection' => [
                'residentKey' => 'required',
                'requireResidentKey' => true,
                'userVerification' => 'required',
            ],
        ];
    }

    /**
     * Valida e salva uma nova Passkey registrada pelo navegador.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyAndStoreRegistration(User $user, array $payload, Request $request): WebauthnCredential
    {
        $expectedChallenge = session()->pull('webauthn_register_challenge');
        $expectedRpId = session()->pull('webauthn_register_rp_id') ?: $this->rpId($request);
        session()->forget('webauthn_register_uv');

        if (! is_string($expectedChallenge) || $expectedChallenge === '') {
            throw new RuntimeException('Desafio de registro WebAuthn expirado ou ausente. Tente novamente.');
        }

        $clientDataJsonB64 = (string) ($payload['clientDataJSON'] ?? '');
        $this->verifyClientData($clientDataJsonB64, 'webauthn.create', $expectedChallenge, $expectedRpId);

        $publicKeySpkiB64 = (string) ($payload['publicKeySpki'] ?? '');
        if ($publicKeySpkiB64 === '') {
            throw new RuntimeException('O navegador não forneceu a chave pública SPKI da Passkey.');
        }

        $spkiDer = $this->base64UrlDecode($publicKeySpkiB64);
        $pem = $this->spkiDerToPem($spkiDer);

        $alg = isset($payload['publicKeyAlg']) && is_numeric($payload['publicKeyAlg'])
            ? (int) $payload['publicKeyAlg']
            : -7;

        if (! in_array($alg, [-7, -257], true)) {
            throw new RuntimeException('Algoritmo de chave pública não suportado para Passkey.');
        }

        $credentialId = (string) ($payload['id'] ?? '');
        if ($credentialId === '') {
            throw new RuntimeException('Identificador da credencial WebAuthn ausente.');
        }

        if (WebauthnCredential::where('credential_id', $credentialId)->exists()) {
            throw new RuntimeException('Esta Passkey já está cadastrada no sistema.');
        }

        $name = trim((string) ($payload['name'] ?? ''));

        return WebauthnCredential::create([
            'user_id' => $user->id,
            'credential_id' => $credentialId,
            'public_key' => $pem,
            'attestation_type' => 'spki:'.$alg,
            'sign_count' => 0,
            'user_handle' => $user->uuid ?: (string) $user->id,
            'name' => $name !== '' ? mb_substr($name, 0, 100) : 'Minha Passkey',
        ]);
    }

    /**
     * Gera as opções WebAuthn para autenticação (login) via Passkey.
     *
     * @return array<string, mixed>
     */
    public function generateAuthenticationOptions(?string $email, Request $request): array
    {
        $challenge = $this->base64UrlEncode(random_bytes(32));
        $rpId = $this->rpId($request);

        session([
            'webauthn_login_challenge' => $challenge,
            'webauthn_login_rp_id' => $rpId,
            'webauthn_login_uv' => 'required',
        ]);

        $allowCredentials = [];
        if ($email !== null && trim($email) !== '') {
            $user = User::where('email', trim($email))->first();
            if ($user && $user->isActive()) {
                $allowCredentials = $user->webauthnCredentials()
                    ->pluck('credential_id')
                    ->map(fn (string $id): array => [
                        'type' => 'public-key',
                        'id' => $id,
                    ])
                    ->values()
                    ->all();
            }
        }

        return [
            'challenge' => $challenge,
            'rpId' => $rpId,
            'timeout' => 60000,
            'userVerification' => 'required',
            'allowCredentials' => $allowCredentials,
        ];
    }

    /**
     * Verifica a asserção criptográfica retornada pelo autenticador e devolve a credencial validada.
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyAssertion(array $payload, Request $request): WebauthnCredential
    {
        $expectedChallenge = session()->pull('webauthn_login_challenge');
        $expectedRpId = session()->pull('webauthn_login_rp_id') ?: $this->rpId($request);
        session()->forget('webauthn_login_uv');

        if (! is_string($expectedChallenge) || $expectedChallenge === '') {
            throw new RuntimeException('Desafio de autenticação expirado. Tente entrar com Passkey novamente.');
        }

        $credentialId = (string) ($payload['id'] ?? '');
        $credential = WebauthnCredential::with('user')->where('credential_id', $credentialId)->first();

        if (! $credential) {
            throw new RuntimeException('CREDENTIAL_NOT_FOUND');
        }

        if (! $credential->user || ! $credential->user->isActive()) {
            throw new RuntimeException('Sua conta ainda não foi ativada ou está inativa.');
        }

        if (! str_contains((string) $credential->public_key, 'BEGIN PUBLIC KEY')) {
            throw new RuntimeException('LEGACY_PASSKEY');
        }

        $clientDataJsonB64 = (string) ($payload['clientDataJSON'] ?? '');
        $clientDataRaw = $this->verifyClientData($clientDataJsonB64, 'webauthn.get', $expectedChallenge, $expectedRpId);

        $authDataRaw = $this->base64UrlDecode((string) ($payload['authenticatorData'] ?? ''));
        $signatureRaw = $this->base64UrlDecode((string) ($payload['signature'] ?? ''));

        if (strlen($authDataRaw) < 37) {
            throw new RuntimeException('Dados do autenticador WebAuthn inválidos.');
        }

        // 1. Verifica RP ID hash (primeiros 32 bytes)
        $rpIdHash = substr($authDataRaw, 0, 32);
        if (! hash_equals(hash('sha256', $expectedRpId, true), $rpIdHash)) {
            throw new RuntimeException('RP ID da Passkey não corresponde a este servidor.');
        }

        // 2. Verifica flags User Present (bit 0 = 0x01) e User Verified (bit 2 = 0x04)
        $flags = ord($authDataRaw[32]);
        if (($flags & 0x01) === 0) {
            throw new RuntimeException('Presença do usuário não confirmada pelo autenticador.');
        }

        if (($flags & 0x04) === 0) {
            throw new RuntimeException('Verificação do usuário (biometria ou PIN) não confirmada pelo autenticador.');
        }

        // 3. Verifica assinatura criptográfica (ES256 / RS256 com SHA-256)
        $signedPayload = $authDataRaw.hash('sha256', $clientDataRaw, true);
        $publicKey = openssl_pkey_get_public((string) $credential->public_key);
        if ($publicKey === false) {
            throw new RuntimeException('Chave pública armazenada inválida.');
        }

        $verified = openssl_verify($signedPayload, $signatureRaw, $publicKey, OPENSSL_ALGO_SHA256);
        if ($verified !== 1) {
            throw new RuntimeException('Assinatura criptográfica da Passkey inválida.');
        }

        // 4. Verifica contador anti-clonagem (bytes 33..36, big-endian uint32)
        // Se o contador armazenado já é > 0, qualquer regressão (inclusive 0) indica clonagem/anomalia.
        $unpacked = unpack('Ncount', substr($authDataRaw, 33, 4));
        $incomingSignCount = (int) ($unpacked['count'] ?? 0);

        if ($credential->sign_count > 0 && $incomingSignCount <= $credential->sign_count) {
            throw new RuntimeException('Contador de uso da Passkey inválido (possível credencial clonada).');
        }

        if ($incomingSignCount > $credential->sign_count) {
            $credential->update(['sign_count' => $incomingSignCount]);
        }

        return $credential;
    }

    /**
     * Converte bytes SPKI DER em PEM e valida via OpenSSL.
     */
    public function spkiDerToPem(string $spkiDer): string
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($spkiDer), 64, "\n")
            ."-----END PUBLIC KEY-----\n";

        if (openssl_pkey_get_public($pem) === false) {
            throw new RuntimeException('Chave pública SPKI inválida.');
        }

        return $pem;
    }

    /**
     * Valida type, challenge e origin exato (scheme + host + port) dentro de clientDataJSON e devolve os bytes brutos.
     */
    private function verifyClientData(
        string $clientDataJsonB64,
        string $expectedType,
        string $expectedChallenge,
        string $expectedRpId
    ): string {
        $raw = $this->base64UrlDecode($clientDataJsonB64);
        $data = json_decode($raw, true);

        if (! is_array($data)) {
            throw new RuntimeException('clientDataJSON inválido.');
        }

        if (($data['type'] ?? '') !== $expectedType) {
            throw new RuntimeException('Tipo de operação WebAuthn inválido.');
        }

        if (! isset($data['challenge']) || ! hash_equals($expectedChallenge, (string) $data['challenge'])) {
            throw new RuntimeException('Desafio WebAuthn não corresponde ao da sessão.');
        }

        $origin = (string) ($data['origin'] ?? '');
        $normalizedOrigin = $this->normalizeOrigin($origin);
        $expectedOrigin = $this->expectedOrigin($expectedRpId);

        if ($normalizedOrigin === null || ! hash_equals($expectedOrigin, $normalizedOrigin)) {
            throw new RuntimeException('Origem da requisição WebAuthn não autorizada.');
        }

        return $raw;
    }

    private function expectedOrigin(string $expectedRpId): string
    {
        $appUrl = (string) config('app.url', 'http://localhost');
        $scheme = strtolower((string) (parse_url($appUrl, PHP_URL_SCHEME) ?: 'http'));
        $host = strtolower((string) (parse_url($appUrl, PHP_URL_HOST) ?: $expectedRpId));
        $port = parse_url($appUrl, PHP_URL_PORT);

        if (app()->isProduction() && $host !== 'localhost' && $host !== '127.0.0.1') {
            $scheme = 'https';
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;
        if ($port !== null && (int) $port !== $defaultPort) {
            return "{$scheme}://{$host}:{$port}";
        }

        return "{$scheme}://{$host}";
    }

    private function normalizeOrigin(string $origin): ?string
    {
        $parts = parse_url($origin);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if ($host !== 'localhost' && $host !== '127.0.0.1' && $scheme !== 'https' && app()->isProduction()) {
            return null;
        }

        $port = $parts['port'] ?? null;
        $defaultPort = $scheme === 'https' ? 443 : 80;

        if ($port !== null && (int) $port !== $defaultPort) {
            return "{$scheme}://{$host}:{$port}";
        }

        return "{$scheme}://{$host}";
    }
}
