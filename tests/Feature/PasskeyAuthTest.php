<?php

use App\Models\User;
use App\Models\WebauthnCredential;
use App\Services\PasskeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function generateEcKeypairBase64UrlSpki(): array
{
    $privateKey = openssl_pkey_new([
        'curve_name' => 'prime256v1',
        'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);

    $details = openssl_pkey_get_details($privateKey);
    $pemPublic = $details['key'];

    $base64Body = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----|\s+/', '', $pemPublic);
    $derBytes = base64_decode($base64Body, true);

    $service = new PasskeyService;

    return [
        'privateKey' => $privateKey,
        'pemPublic' => $pemPublic,
        'spkiB64Url' => $service->base64UrlEncode($derBytes),
    ];
}

test('usuario autenticado registra passkey com validacao de clientDataJSON e chave publica SPKI', function () {
    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    $service = new PasskeyService;
    $keys = generateEcKeypairBase64UrlSpki();

    $optionsRes = $this->actingAs($professor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->getJson(route('security.passkey.options'));
    $optionsRes->assertOk()->assertJsonStructure(['challenge', 'rp' => ['id', 'name'], 'user' => ['id']]);

    $challenge = $optionsRes->json('challenge');
    $clientData = json_encode([
        'type' => 'webauthn.create',
        'challenge' => $challenge,
        'origin' => 'http://localhost:8000',
    ]);

    $storeRes = $this->actingAs($professor)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->postJson(route('security.passkey.store'), [
            'id' => 'cred-id-123',
            'clientDataJSON' => $service->base64UrlEncode($clientData),
            'publicKeySpki' => $keys['spkiB64Url'],
            'publicKeyAlg' => -7,
            'name' => 'Notebook Trabalho',
        ]);

    $storeRes->assertOk()->assertJson(['success' => true]);

    $cred = WebauthnCredential::where('credential_id', 'cred-id-123')->first();
    expect($cred)->not->toBeNull()
        ->and($cred->user_id)->toBe($professor->id)
        ->and($cred->name)->toBe('Notebook Trabalho')
        ->and($cred->public_key)->toContain('BEGIN PUBLIC KEY');
});

test('usuario faz login direto com passkey valida sem exigir TOTP', function () {
    $service = new PasskeyService;
    $keys = generateEcKeypairBase64UrlSpki();

    $professor = User::factory()->create([
        'email' => 'prof.passkey@corujinha.edu',
        'role' => 'professor',
        'status' => 'active',
        'totp_secret' => 'SECRETBASE32ABC',
        'totp_enabled_at' => now(),
    ]);

    $credentialId = $service->base64UrlEncode(random_bytes(16));
    $cred = WebauthnCredential::create([
        'user_id' => $professor->id,
        'credential_id' => $credentialId,
        'public_key' => $keys['pemPublic'],
        'attestation_type' => 'spki:-7',
        'sign_count' => 1,
        'user_handle' => (string) $professor->id,
        'name' => 'Meu Celular',
    ]);

    $optRes = $this->postJson(route('login.passkey.options'), [
        'email' => $professor->email,
    ]);
    $optRes->assertOk();
    expect($optRes->json('allowCredentials.0.id'))->toBe($credentialId);

    $challenge = $optRes->json('challenge');
    $rpId = $optRes->json('rpId');

    $clientDataRaw = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $challenge,
        'origin' => 'http://localhost:8000',
    ]);

    // authenticatorData: rpIdHash (32 bytes) + flags (0x05 = UP + UV) + signCount (uint32 BE = 2)
    $authDataRaw = hash('sha256', $rpId, true).chr(0x05).pack('N', 2);
    $signedPayload = $authDataRaw.hash('sha256', $clientDataRaw, true);

    openssl_sign($signedPayload, $signatureRaw, $keys['privateKey'], OPENSSL_ALGO_SHA256);

    $verifyRes = $this->postJson(route('login.passkey.verify'), [
        'id' => $credentialId,
        'clientDataJSON' => $service->base64UrlEncode($clientDataRaw),
        'authenticatorData' => $service->base64UrlEncode($authDataRaw),
        'signature' => $service->base64UrlEncode($signatureRaw),
    ]);

    $verifyRes->assertOk()
        ->assertJson([
            'success' => true,
            'redirect_url' => route('professor.salas.index'),
        ]);

    expect(auth()->id())->toBe($professor->id);

    $cred->refresh();
    expect($cred->sign_count)->toBe(2);
});

test('login com passkey retorna 404 para credencial desconhecida e rejeita assinatura adulterada', function () {
    $service = new PasskeyService;
    $keys = generateEcKeypairBase64UrlSpki();

    $professor = User::factory()->create(['role' => 'professor', 'status' => 'active']);
    WebauthnCredential::create([
        'user_id' => $professor->id,
        'credential_id' => 'cred-valida',
        'public_key' => $keys['pemPublic'],
        'attestation_type' => 'spki:-7',
        'sign_count' => 0,
        'user_handle' => (string) $professor->id,
        'name' => 'Chave Teste',
    ]);

    // Credencial inexistente -> HTTP 404 para permitir signalUnknownCredential
    $opt1 = $this->postJson(route('login.passkey.options'));
    $clientData1 = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $opt1->json('challenge'),
        'origin' => 'http://localhost:8000',
    ]);
    $authData1 = hash('sha256', $opt1->json('rpId'), true).chr(0x05).pack('N', 1);

    $this->postJson(route('login.passkey.verify'), [
        'id' => 'cred-inexistente',
        'clientDataJSON' => $service->base64UrlEncode($clientData1),
        'authenticatorData' => $service->base64UrlEncode($authData1),
        'signature' => $service->base64UrlEncode('assinatura-falsa'),
    ])->assertStatus(404);

    // Assinatura adulterada -> HTTP 422 e usuário não autenticado
    $opt2 = $this->postJson(route('login.passkey.options'));
    $clientData2 = json_encode([
        'type' => 'webauthn.get',
        'challenge' => $opt2->json('challenge'),
        'origin' => 'http://localhost:8000',
    ]);
    $authData2 = hash('sha256', $opt2->json('rpId'), true).chr(0x05).pack('N', 1);

    $this->postJson(route('login.passkey.verify'), [
        'id' => 'cred-valida',
        'clientDataJSON' => $service->base64UrlEncode($clientData2),
        'authenticatorData' => $service->base64UrlEncode($authData2),
        'signature' => $service->base64UrlEncode('assinatura-adulterada'),
    ])->assertStatus(422);

    expect(auth()->check())->toBeFalse();
});
