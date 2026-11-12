<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\DB;

class TotpService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Gera um segredo aleatório em Base32 (160 bits).
     */
    public function generateSecret(int $length = 32): string
    {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[ord($bytes[$i]) % 32];
        }

        return $secret;
    }

    /**
     * Valida um código TOTP de 6 dígitos com tolerância de +/- 1 ciclo (30s).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        return $this->findMatchingSlice($secret, $code, $window) !== null;
    }

    /**
     * Retorna o timeSlice correspondente ao código informado se válido, ou null.
     */
    public function verifyAndGetSlice(string $secret, string $code, int $window = 1): ?int
    {
        return $this->findMatchingSlice($secret, $code, $window);
    }

    /**
     * Valida um código TOTP garantindo uso único por janela temporal (proteção anti-replay).
     */
    public function verifyOnce(User $user, string $code, int $window = 1): bool
    {
        $secret = trim((string) $user->totp_secret);
        $code = trim($code);
        if ($secret === '' || strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        return DB::transaction(function () use ($user, $code, $window): bool {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();
            if (! $lockedUser || ! $lockedUser->isTotpEnabled()) {
                return false;
            }

            $matchedSlice = $this->findMatchingSlice((string) $lockedUser->totp_secret, $code, $window);
            if ($matchedSlice === null) {
                return false;
            }

            $lastSlice = (int) ($lockedUser->totp_last_used_slice ?? 0);
            if ($matchedSlice <= $lastSlice) {
                return false;
            }

            $lockedUser->forceFill(['totp_last_used_slice' => $matchedSlice])->save();
            $user->totp_last_used_slice = $matchedSlice;

            return true;
        });
    }

    /**
     * Gera a URI compatível com Google Authenticator e Authy.
     */
    public function getOtpAuthUrl(string $name, string $email, string $secret): string
    {
        $issuer = rawurlencode('Corujinha');
        $label = rawurlencode('Corujinha').':'.rawurlencode($email);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Renderiza localmente o QR Code em formato SVG inline (sem requisições externas).
     */
    public function generateQrCodeSvg(string $otpAuthUri, int $size = 160): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString($otpAuthUri);
    }

    /**
     * Calcula o código de 6 dígitos para um dado timestamp slice.
     */
    public function calculateCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        if ($secretKey === '') {
            return '';
        }

        $time = pack('N*', 0).pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;

        $unpacked = unpack('N', substr($hash, $offset, 4));
        $value = $unpacked[1] & 0x7FFFFFFF;

        $pin = $value % 1000000;

        return str_pad((string) $pin, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Retorna o timeSlice correspondente ao código informado, ou null caso inválido.
     */
    private function findMatchingSlice(string $secret, string $code, int $window = 1): ?int
    {
        $secret = trim($secret);
        $code = trim($code);
        if ($secret === '' || strlen($code) !== 6 || ! ctype_digit($code)) {
            return null;
        }

        $currentSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            $slice = $currentSlice + $i;
            $calculated = $this->calculateCode($secret, $slice);
            if ($calculated !== '' && hash_equals($calculated, $code)) {
                return $slice;
            }
        }

        return null;
    }

    /**
     * Decodifica string Base32 para bytes binários.
     */
    private function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            $val = strpos(self::BASE32_ALPHABET, $char);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
