<?php

namespace App\Services;

use DOMDocument;
use DOMNode;
use DOMText;

class TextHighlightService
{
    /**
     * Aplica os marcadores do aluno (amarelo para comando, azul para pista) no texto original
     * preservando formatação HTML sanitizada (negrito, itálico, sublinhado, taxado e links).
     *
     * @param  string|null  $text  Texto puro ou HTML sanitizado do apoio/enunciado.
     * @param  array<int, array<string, mixed>>  $markers  Lista de marcadores salvos no banco.
     * @param  string|null  $target  Filtro do alvo: 'title', 'support'/'source' ou 'prompt' (opcional).
     * @return string HTML seguro com as tags <mark> estilizadas.
     */
    public static function applyHighlights(?string $text, array $markers = [], ?string $target = null): string
    {
        if (empty($text)) {
            return '';
        }

        $isSupportTarget = in_array($target, ['support', 'source'], true);
        $baseHtml = $isSupportTarget
            ? RichTextSanitizer::toHtml($text)
            : nl2br(e($text));

        // Filtra marcadores pelo alvo se informado
        $filtered = array_filter($markers, function ($m) use ($target) {
            if ($target !== null && isset($m['target'])) {
                if ($target === 'support' || $target === 'source') {
                    return in_array($m['target'], ['support', 'source'], true);
                }

                return $m['target'] === $target;
            }

            return true;
        });

        if (empty($filtered)) {
            return $baseHtml;
        }

        $previousErrorSetting = libxml_use_internal_errors(true);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__hl_root">'.$baseHtml.'</div></body></html>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorSetting);

        $root = $doc->getElementById('__hl_root');
        if (! $root) {
            return $baseHtml;
        }

        /** @var array<int, array{node: DOMText, start: int, end: int, text: string}> $textNodes */
        $textNodes = [];
        $fullPlainText = '';
        $cursor = 0;

        self::collectTextNodes($root, $textNodes, $fullPlainText, $cursor);

        $totalLen = mb_strlen($fullPlainText, 'UTF-8');
        if ($totalLen === 0) {
            return $baseHtml;
        }

        // Normaliza e valida os marcadores contra o texto plano extraído do DOM
        $validMarkers = [];
        foreach ($filtered as $m) {
            $snippet = trim((string) ($m['text'] ?? ''));
            if ($snippet === '') {
                continue;
            }

            $kind = $m['kind'] ?? 'evidence';
            $start = $m['start'] ?? null;
            $end = $m['end'] ?? null;

            $resolvedStart = null;
            $resolvedEnd = null;

            if (is_numeric($start) && is_numeric($end)) {
                $s = (int) $start;
                $e = (int) $end;
                if ($s >= 0 && $e > $s && $e <= $totalLen) {
                    $slice = mb_substr($fullPlainText, $s, $e - $s, 'UTF-8');
                    if (trim($slice) === $snippet || str_contains($slice, $snippet)) {
                        $resolvedStart = $s;
                        $resolvedEnd = $e;
                    }
                }
            }

            // Fallback por busca de substring (cobre legado com quebras de linha convertidas em <p>)
            if ($resolvedStart === null) {
                $pos = mb_strpos($fullPlainText, $snippet, 0, 'UTF-8');
                if ($pos !== false) {
                    $resolvedStart = $pos;
                    $resolvedEnd = $pos + mb_strlen($snippet, 'UTF-8');
                }
            }

            if ($resolvedStart !== null && $resolvedEnd !== null) {
                $validMarkers[] = [
                    'start' => $resolvedStart,
                    'end' => $resolvedEnd,
                    'kind' => $kind,
                ];
            }
        }

        if (empty($validMarkers)) {
            return $baseHtml;
        }

        usort($validMarkers, fn ($a, $b) => $a['start'] <=> $b['start']);

        // Remove sobreposições mantendo a ordem crescente
        $nonOverlapping = [];
        $lastEnd = 0;
        foreach ($validMarkers as $vm) {
            if ($vm['start'] < $lastEnd) {
                continue;
            }
            $nonOverlapping[] = $vm;
            $lastEnd = $vm['end'];
        }

        // Aplica as tags <mark> dividindo cada DOMText afetado
        foreach ($textNodes as $entry) {
            $node = $entry['node'];
            $nodeStart = $entry['start'];
            $nodeEnd = $entry['end'];
            $nodeText = $entry['text'];

            $overlapping = array_filter(
                $nonOverlapping,
                fn ($m) => $m['end'] > $nodeStart && $m['start'] < $nodeEnd
            );

            if (empty($overlapping)) {
                continue;
            }

            $fragment = $doc->createDocumentFragment();
            $localCursor = 0;
            $nodeLen = mb_strlen($nodeText, 'UTF-8');

            foreach ($overlapping as $m) {
                $relStart = max(0, $m['start'] - $nodeStart);
                $relEnd = min($nodeLen, $m['end'] - $nodeStart);

                if ($relStart > $localCursor) {
                    $before = mb_substr($nodeText, $localCursor, $relStart - $localCursor, 'UTF-8');
                    $fragment->appendChild($doc->createTextNode($before));
                }

                if ($relEnd > $relStart) {
                    $marked = mb_substr($nodeText, $relStart, $relEnd - $relStart, 'UTF-8');
                    $markEl = $doc->createElement('mark');

                    $kindClass = ($m['kind'] === 'command')
                        ? 'bg-broom-300 text-broom-950 font-bold px-1.5 py-0.5 rounded shadow-2xs'
                        : 'bg-sky-blue-200 text-sky-blue-950 font-bold px-1.5 py-0.5 rounded shadow-2xs';

                    $title = ($m['kind'] === 'command')
                        ? 'Comando demarcado por você'
                        : 'Pista demarcada por você';

                    $markEl->setAttribute('class', $kindClass);
                    $markEl->setAttribute('title', $title);
                    $markEl->appendChild($doc->createTextNode($marked));
                    $fragment->appendChild($markEl);
                }

                $localCursor = max($localCursor, $relEnd);
            }

            if ($localCursor < $nodeLen) {
                $after = mb_substr($nodeText, $localCursor, null, 'UTF-8');
                $fragment->appendChild($doc->createTextNode($after));
            }

            $node->parentNode?->replaceChild($fragment, $node);
        }

        $resultHtml = '';
        foreach ($root->childNodes as $child) {
            $resultHtml .= $doc->saveHTML($child);
        }

        return $resultHtml;
    }

    /**
     * @param  array<int, array{node: DOMText, start: int, end: int, text: string}>  $textNodes
     */
    private static function collectTextNodes(DOMNode $node, array &$textNodes, string &$fullPlainText, int &$cursor): void
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $val = $child->nodeValue ?? '';
                $len = mb_strlen($val, 'UTF-8');
                if ($len > 0) {
                    $textNodes[] = [
                        'node' => $child,
                        'start' => $cursor,
                        'end' => $cursor + $len,
                        'text' => $val,
                    ];
                    $fullPlainText .= $val;
                    $cursor += $len;
                }
            } elseif ($child->hasChildNodes()) {
                self::collectTextNodes($child, $textNodes, $fullPlainText, $cursor);
            }
        }
    }
}
