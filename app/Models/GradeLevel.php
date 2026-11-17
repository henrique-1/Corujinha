<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GradeLevel extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'normalized_name',
        'is_default',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Normaliza uma string de turma / ano escolar para unificar valores similares.
     * Exemplo: '3º Ano EM', '3 EM', '3º ano em' -> '3anoem'
     *          '6 º Ano', '6º ano', '6 ano' -> '6ano'
     */
    public static function normalize(string $name): string
    {
        $raw = trim($name);
        if ($raw === '') {
            return '';
        }

        $lower = mb_strtolower($raw, 'UTF-8');
        $ascii = Str::ascii($lower);

        // Remove símbolos ordinais e caracteres de grau (° e º)
        $ascii = str_replace(['º', 'ª', '°'], '', $ascii);

        $hasEm = preg_match('/\b(em|ensino\s*medio|medio)\b/i', $ascii) || str_contains($ascii, 'em');

        // Ensino Médio: 1º, 2º ou 3º
        if ($hasEm && preg_match('/([1-3])/', $ascii, $matches)) {
            return $matches[1].'anoem';
        }

        // Anos escolares comuns (Fundamental 1 a 9)
        if (! $hasEm && preg_match('/([1-9])/', $ascii, $matches)) {
            return $matches[1].'ano';
        }

        // Normalização geral: apenas letras e números
        return preg_replace('/[^a-z0-9]/', '', $ascii) ?: '';
    }

    /**
     * Busca ou cadastra uma turma/ano escolar combinando variações semelhantes.
     */
    public static function findOrCreateSimilar(?string $rawName): ?string
    {
        if ($rawName === null || trim($rawName) === '') {
            return null;
        }

        $trimmed = trim($rawName);
        $normalized = static::normalize($trimmed);

        if ($normalized === '') {
            return $trimmed;
        }

        // 1. Busca exata por normalized_name
        $existing = static::where('normalized_name', $normalized)->first();
        if ($existing) {
            return $existing->name;
        }

        // 2. Busca aproximada (Levenshtein e similaridade) em registros existentes
        $all = static::all();
        $bestMatch = null;
        $highestSimilarity = 0.0;

        foreach ($all as $grade) {
            if ($grade->normalized_name === $normalized) {
                return $grade->name;
            }

            similar_text($grade->normalized_name, $normalized, $percent);
            if ($percent > $highestSimilarity) {
                $highestSimilarity = $percent;
                $bestMatch = $grade;
            }

            if (strlen($normalized) >= 4 && strlen($grade->normalized_name) >= 4) {
                $lev = levenshtein($grade->normalized_name, $normalized);
                if ($lev <= 2 && $percent >= 80.0) {
                    return $grade->name;
                }
            }
        }

        if ($highestSimilarity >= 88.0 && $bestMatch) {
            return $bestMatch->name;
        }

        // 3. Não encontrou correspondente: formata e cadastra no banco
        $alphaOnly = preg_replace('/[^a-zA-Z]/', '', $trimmed);
        if ($alphaOnly !== '' && strlen($trimmed) <= 5 && strtoupper($trimmed) === $trimmed) {
            $formattedName = $trimmed;
        } else {
            $formattedName = mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
        }

        $created = static::create([
            'name' => $formattedName,
            'normalized_name' => $normalized,
            'is_default' => false,
        ]);

        return $created->name;
    }
}
