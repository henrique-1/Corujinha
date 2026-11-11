<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    /**
     * Tags HTML permitidas no corpo do texto de apoio.
     *
     * @var array<int, string>
     */
    private const ALLOWED_TAGS = [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        's',
        'del',
        'a',
    ];

    /**
     * Tags cujo conteúdo interno também deve ser removido por segurança.
     *
     * @var array<int, string>
     */
    private const DROP_WITH_CONTENT_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'svg',
        'math',
        'noscript',
        'template',
    ];

    /**
     * Sanitiza HTML vindo do editor de texto complexo usando lista branca estrita.
     */
    public static function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        // Texto puro legado sem tags HTML: preserva intacto ou sanitiza normalmente
        if (! self::containsHtmlTags($html)) {
            $trimmed = trim($html);

            return $trimmed === '' ? null : $html;
        }

        $previousErrorSetting = libxml_use_internal_errors(true);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="__sanitize_root">'.$html.'</div></body></html>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorSetting);

        $root = $doc->getElementById('__sanitize_root');
        if (! $root) {
            return null;
        }

        self::sanitizeChildren($root, $doc);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $doc->saveHTML($child);
        }

        $plainCheck = trim(str_replace("\xC2\xA0", ' ', strip_tags($output)));
        if ($plainCheck === '') {
            return null;
        }

        return $output;
    }

    /**
     * Converte texto legado (texto puro) ou HTML sanitizado em HTML pronto para exibição.
     */
    public static function toHtml(?string $content): string
    {
        if ($content === null || trim($content) === '') {
            return '';
        }

        if (! self::containsHtmlTags($content)) {
            $lines = preg_split("/\r\n|\n|\r/", $content) ?: [$content];
            $paragraphs = array_map(
                fn (string $line): string => $line === '' ? '<p><br></p>' : '<p>'.e($line).'</p>',
                $lines
            );

            return implode('', $paragraphs);
        }

        return self::sanitize($content) ?? '';
    }

    /**
     * Verifica se a string contém marcações HTML conhecidas.
     */
    public static function containsHtmlTags(string $content): bool
    {
        return (bool) preg_match('/<\s*\/?\s*[a-z][a-z0-9]*(\s+[^>]*)?>/i', $content);
    }

    private static function sanitizeChildren(DOMNode $parent, DOMDocument $doc): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);

                continue;
            }

            if ($node->nodeType !== XML_ELEMENT_NODE || ! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT_TAGS, true)) {
                $parent->removeChild($node);

                continue;
            }

            // Normaliza <div> para <p> mantendo os filhos
            if ($tag === 'div') {
                $p = $doc->createElement('p');
                while ($node->firstChild) {
                    $p->appendChild($node->firstChild);
                }
                $parent->replaceChild($p, $node);
                self::sanitizeChildren($p, $doc);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                // Desembrulha o conteúdo seguro de tags não listadas (ex: span, font)
                self::sanitizeChildren($node, $doc);
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            // Limpa todos os atributos de tags comuns; valida <a>
            if ($tag === 'a') {
                $href = trim($node->getAttribute('href'));
                $safeHref = self::sanitizeUrl($href);

                // Remove todos os atributos atuais
                while ($node->attributes->length > 0) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }

                if ($safeHref === null) {
                    self::sanitizeChildren($node, $doc);
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);

                    continue;
                }

                $node->setAttribute('href', $safeHref);
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            } else {
                while ($node->attributes->length > 0) {
                    $node->removeAttributeNode($node->attributes->item(0));
                }
            }

            self::sanitizeChildren($node, $doc);
        }
    }

    private static function sanitizeUrl(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        // Remove caracteres de controle e espaços invisíveis usados para ofuscar esquemas
        $cleaned = preg_replace('/[\x00-\x20\x7F]+/', '', $url) ?? '';
        if ($cleaned === '') {
            return null;
        }

        $parsed = parse_url($cleaned);
        if ($parsed === false || empty($parsed['scheme'])) {
            return null;
        }

        $scheme = strtolower($parsed['scheme']);
        if (! in_array($scheme, ['http', 'https', 'mailto'], true)) {
            return null;
        }

        return $url;
    }
}
