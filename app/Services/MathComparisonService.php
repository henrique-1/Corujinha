<?php

namespace App\Services;

class MathComparisonService
{
    /**
     * Compara a resposta dada pelo aluno com a resposta esperada cadastrada pelo professor.
     */
    public function isEquivalent(?string $given, ?string $expected): bool
    {
        if ($given === null || $expected === null) {
            return false;
        }

        $cleanGiven = $this->normalize($given);
        $cleanExpected = $this->normalize($expected);

        if ($cleanGiven === '' || $cleanExpected === '') {
            return false;
        }

        // 1. Igualdade direta após normalização de sintaxe e espaços
        if ($cleanGiven === $cleanExpected) {
            return true;
        }

        // 2. Normalização de frações LaTeX \frac{a}{b} -> a/b
        $fractionGiven = $this->normalizeFractions($cleanGiven);
        $fractionExpected = $this->normalizeFractions($cleanExpected);

        if ($fractionGiven === $fractionExpected) {
            return true;
        }

        // 3. Comparação numérica direta se ambos forem numéricos ou frações numéricas
        $numGiven = $this->parseNumber($fractionGiven);
        $numExpected = $this->parseNumber($fractionExpected);

        if ($numGiven !== null && $numExpected !== null) {
            return abs($numGiven - $numExpected) < 0.0001;
        }

        // 4. Comparação removendo prefixos comuns de variável (ex.: "x=" ou "r=")
        $strippedGiven = preg_replace('/^[a-z]\s*=\s*/', '', $fractionGiven);
        $strippedExpected = preg_replace('/^[a-z]\s*=\s*/', '', $fractionExpected);

        if ($strippedGiven === $strippedExpected) {
            return true;
        }

        $numStrippedGiven = $this->parseNumber($strippedGiven);
        $numStrippedExpected = $this->parseNumber($strippedExpected);

        if ($numStrippedGiven !== null && $numStrippedExpected !== null) {
            return abs($numStrippedGiven - $numStrippedExpected) < 0.0001;
        }

        return false;
    }

    /**
     * Normaliza a string matemática (remove delimitadores LaTeX, unifica vírgula decimal e remove espaços).
     */
    public function normalize(string $value): string
    {
        $v = trim(mb_strtolower($value));

        // Remove delimitadores de fórmula LaTeX
        $v = str_replace(['$$', '$', '\(', '\)', '\[', '\]'], '', $v);

        // Remove comandos de espaçamento LaTeX comuns
        $v = str_replace(['\quad', '\qquad', '\,', '\;', '\:', '\ '], '', $v);

        // Converte operadores LaTeX para equivalentes ASCII
        $v = str_replace(['\cdot', '\times', '\ast'], '*', $v);
        $v = str_replace(['\div'], '/', $v);

        // Converte vírgula decimal para ponto decimal (ex: 3,14 -> 3.14)
        $v = preg_replace('/(\d+),(\d+)/', '$1.$2', $v);

        // Remove todos os espaços em branco
        $v = preg_replace('/\s+/', '', $v);

        return $v;
    }

    /**
     * Converte \frac{a}{b} para a/b.
     */
    public function normalizeFractions(string $value): string
    {
        return preg_replace_callback('/\\\\frac\{([^{}]+)\}\{([^{}]+)\}/', function ($matches) {
            return $matches[1].'/'.$matches[2];
        }, $value);
    }

    /**
     * Converte string numérica ou fração numérica simples (ex.: "1/2" ou "3.5") para float.
     */
    public function parseNumber(string $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Fração simples "a/b"
        if (preg_match('/^(-?\d+(?:\.\d+)?)\/(\d+(?:\.\d+)?)$/', $value, $matches)) {
            $denominator = (float) $matches[2];
            if ($denominator != 0.0) {
                return (float) $matches[1] / $denominator;
            }
        }

        return null;
    }
}
