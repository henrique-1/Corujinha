<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class CpfRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('O CPF informado deve ser um texto.');

            return;
        }

        $cpf = preg_replace('/\D/', '', $value);

        if (strlen($cpf) !== 11) {
            $fail('O CPF deve conter exatamente 11 dígitos.');

            return;
        }

        // Rejeita sequências de dígitos repetidos (ex.: 111.111.111-11)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado é inválido.');

            return;
        }

        // Validação do primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : (11 - $remainder);

        if ((int) $cpf[9] !== $digit1) {
            $fail('O CPF informado é inválido.');

            return;
        }

        // Validação do segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : (11 - $remainder);

        if ((int) $cpf[10] !== $digit2) {
            $fail('O CPF informado é inválido.');
        }
    }

    /**
     * Remove caracteres não numéricos do CPF.
     */
    public static function sanitize(?string $cpf): ?string
    {
        if ($cpf === null) {
            return null;
        }

        return preg_replace('/\D/', '', $cpf);
    }

    /**
     * Formata um CPF numérico para o formato XXX.XXX.XXX-XX.
     */
    public static function format(?string $cpf): ?string
    {
        $digits = self::sanitize($cpf);
        if (! $digits || strlen($digits) !== 11) {
            return $cpf;
        }

        return vsprintf('%s%s%s.%s%s%s.%s%s%s-%s%s', str_split($digits));
    }
}
