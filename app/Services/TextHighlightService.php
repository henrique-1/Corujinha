<?php

namespace App\Services;

class TextHighlightService
{
    /**
     * Aplica os marcadores do aluno (amarelo para comando, azul para pista) no texto original.
     *
     * @param  string|null  $text  Texto puro de apoio ou enunciado.
     * @param  array<int, array<string, mixed>>  $markers  Lista de marcadores salvos no banco.
     * @param  string  $target  Filtro do alvo: 'support' ou 'prompt' (opcional).
     * @return string HTML com as tags <mark> estilizadas.
     */
    public static function applyHighlights(?string $text, array $markers = [], ?string $target = null): string
    {
        if (empty($text)) {
            return '';
        }

        // Filtra marcadores pelo alvo se informado
        $filtered = array_filter($markers, function ($m) use ($target) {
            if ($target !== null && isset($m['target'])) {
                // Aceita equivalências: 'source' e 'support' referem-se ao texto de apoio
                if ($target === 'support' || $target === 'source') {
                    return in_array($m['target'], ['support', 'source']);
                }

                return $m['target'] === $target;
            }

            return true;
        });

        if (empty($filtered)) {
            return nl2br(e($text));
        }

        // Normaliza offsets válidos
        $validMarkers = [];
        foreach ($filtered as $m) {
            $snippet = trim($m['text'] ?? '');
            if ($snippet === '') {
                continue;
            }

            $kind = $m['kind'] ?? 'evidence';
            $start = $m['start'] ?? null;
            $end = $m['end'] ?? null;

            // Se não tiver start/end válidos, busca a primeira ocorrência
            if ($start === null || $end === null || ! is_numeric($start) || ! is_numeric($end)) {
                $pos = mb_strpos($text, $snippet);
                if ($pos !== false) {
                    $start = $pos;
                    $end = $pos + mb_strlen($snippet);
                } else {
                    continue;
                }
            }

            $validMarkers[] = [
                'start' => (int) $start,
                'end' => (int) $end,
                'snippet' => $snippet,
                'kind' => $kind,
            ];
        }

        if (empty($validMarkers)) {
            return nl2br(e($text));
        }

        // Ordena por início crescente
        usort($validMarkers, fn ($a, $b) => $a['start'] <=> $b['start']);

        $resultHtml = '';
        $lastIdx = 0;
        $textLen = mb_strlen($text);

        foreach ($validMarkers as $vm) {
            if ($vm['start'] < $lastIdx || $vm['start'] >= $textLen) {
                continue; // evita sobreposição
            }

            // Pedaço antes da marca
            $before = mb_substr($text, $lastIdx, $vm['start'] - $lastIdx);
            $resultHtml .= nl2br(e($before));

            // Trecho marcado
            $markLen = min($vm['end'] - $vm['start'], $textLen - $vm['start']);
            $highlighted = mb_substr($text, $vm['start'], $markLen);

            $kindClass = ($vm['kind'] === 'command')
                ? 'bg-broom-300 text-broom-950 font-bold px-1.5 py-0.5 rounded shadow-2xs'
                : 'bg-sky-blue-200 text-sky-blue-950 font-bold px-1.5 py-0.5 rounded shadow-2xs';

            $title = ($vm['kind'] === 'command') ? 'Comando demarcado por você' : 'Pista demarcada por você';

            $resultHtml .= '<mark class="'.$kindClass.'" title="'.e($title).'">'.nl2br(e($highlighted)).'</mark>';

            $lastIdx = $vm['start'] + $markLen;
        }

        // Pedaço restante após a última marca
        if ($lastIdx < $textLen) {
            $resultHtml .= nl2br(e(mb_substr($text, $lastIdx)));
        }

        return $resultHtml;
    }
}
