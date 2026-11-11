/**
 * Utilitários compartilhados para renderização segura de HTML rico (Rich Text)
 * e aplicação de marca-texto (<mark>) sobre nós de texto do DOM sem corromper tags HTML.
 */

let domPurifyPromise = null;

function loadDOMPurify() {
    domPurifyPromise ??= import('dompurify').then((mod) => mod.default ?? mod);
    return domPurifyPromise;
}

const ALLOWED_ELEMENTS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'del', 'a'];
const ALLOWED_ATTRIBUTES = ['href', 'target', 'rel'];

function escapeHtmlText(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Converte texto puro legado em HTML de parágrafos; mantém HTML se já houver marcações.
 */
export function normalizeTextToHtml(raw) {
    const str = String(raw ?? '');
    if (!str.trim()) return '';

    const hasHtml = /<\s*\/?\s*(p|br|strong|b|em|i|u|s|del|a|div|span)\b/i.test(str);
    if (hasHtml) {
        return str;
    }

    return str
        .split(/\r\n|\n|\r/)
        .map((line) => (line === '' ? '<p><br></p>' : `<p>${escapeHtmlText(line)}</p>`))
        .join('');
}

/**
 * Insere HTML não confiável com segurança usando Sanitizer API nativa quando disponível,
 * ou fallback assíncrono via DOMPurify em navegadores sem suporte.
 */
export async function safeSetHTML(el, html) {
    if (!el) return;

    const cleanInput = String(html ?? '');
    if (!cleanInput) {
        el.innerHTML = '';
        return;
    }

    if ('setHTML' in Element.prototype) {
        el.setHTML(cleanInput, {
            sanitizer: {
                elements: ALLOWED_ELEMENTS,
                attributes: ALLOWED_ATTRIBUTES,
            },
        });
    } else {
        const DOMPurify = await loadDOMPurify();
        el.innerHTML = DOMPurify.sanitize(cleanInput, {
            ALLOWED_TAGS: [...ALLOWED_ELEMENTS, '#text'],
            ALLOWED_ATTR: ALLOWED_ATTRIBUTES,
            ALLOW_ARIA_ATTR: false,
            ALLOW_DATA_ATTR: false,
            KEEP_CONTENT: false,
        });
    }

    // Garante segurança extra e abertura externa nos hyperlinks
    el.querySelectorAll('a').forEach((link) => {
        const href = (link.getAttribute('href') || '').trim();
        const isSafe = /^(https?:|mailto:)/i.test(href);
        if (!isSafe) {
            const text = document.createTextNode(link.textContent || '');
            link.replaceWith(text);
        } else {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener noreferrer');
        }
    });
}

function getOffsetAtPoint(root, targetNode, targetOffset) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const probe = document.createRange();
    probe.setStart(root, 0);
    probe.setEnd(targetNode, targetOffset);

    let total = 0;
    let node;
    while ((node = walker.nextNode())) {
        const len = node.nodeValue ? node.nodeValue.length : 0;
        if (node === targetNode) {
            total += targetOffset;
            break;
        }
        if (len > 0 && probe.comparePoint(node, len) <= 0) {
            total += len;
        } else {
            break;
        }
    }

    return total;
}

/**
 * Calcula offsets exatos [start, end] e o texto plano de uma seleção dentro do container.
 */
export function getSelectionOffsets(container, range) {
    if (!container || !range) return null;

    const start = getOffsetAtPoint(container, range.startContainer, range.startOffset);
    const end = getOffsetAtPoint(container, range.endContainer, range.endOffset);

    if (end <= start) return null;

    const fullText = container.textContent || '';
    const text = fullText.slice(start, end);

    if (!text.trim()) return null;

    return { start, end, text };
}

/**
 * Renderiza HTML sanitizado no container e aplica marcações <mark> nos intervalos indicados
 * preservando negrito, itálico, sublinhado, taxado e links.
 */
export async function renderHtmlWithHighlights(container, rawHtmlOrText, markers = [], buildMarkEl = null) {
    if (!container) return;

    const html = normalizeTextToHtml(rawHtmlOrText);
    await safeSetHTML(container, html);

    if (!markers || markers.length === 0) return;

    const fullText = container.textContent || '';
    if (!fullText) return;

    // Resolve offsets ou usa fallback por busca de substring
    const resolved = [];
    for (const m of markers) {
        const snippet = String(m.text ?? '').trim();
        let s = typeof m.start === 'number' ? m.start : null;
        let e = typeof m.end === 'number' ? m.end : null;

        if (s !== null && e !== null && s >= 0 && e > s && e <= fullText.length) {
            if (snippet && !fullText.slice(s, e).includes(snippet) && fullText.slice(s, e).trim() !== snippet) {
                s = null;
                e = null;
            }
        } else {
            s = null;
            e = null;
        }

        if (s === null && snippet) {
            const idx = fullText.indexOf(snippet);
            if (idx !== -1) {
                s = idx;
                e = idx + snippet.length;
            }
        }

        if (s !== null && e !== null && e > s) {
            resolved.push({ ...m, start: s, end: e });
        }
    }

    if (resolved.length === 0) return;

    resolved.sort((a, b) => a.start - b.start);

    const nonOverlapping = [];
    let lastEnd = 0;
    for (const m of resolved) {
        if (m.start < lastEnd) continue;
        nonOverlapping.push(m);
        lastEnd = m.end;
    }

    // Coleta nós de texto antes de mutar o DOM
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let cursor = 0;
    let node;
    while ((node = walker.nextNode())) {
        const val = node.nodeValue || '';
        if (val.length > 0) {
            textNodes.push({
                node,
                start: cursor,
                end: cursor + val.length,
                text: val,
            });
            cursor += val.length;
        }
    }

    for (const entry of textNodes) {
        const overlapping = nonOverlapping.filter(
            (m) => m.end > entry.start && m.start < entry.end
        );
        if (overlapping.length === 0) continue;

        const frag = document.createDocumentFragment();
        let localCursor = 0;

        for (const m of overlapping) {
            const relStart = Math.max(0, m.start - entry.start);
            const relEnd = Math.min(entry.text.length, m.end - entry.start);

            if (relStart > localCursor) {
                frag.appendChild(document.createTextNode(entry.text.slice(localCursor, relStart)));
            }

            if (relEnd > relStart) {
                const sliceText = entry.text.slice(relStart, relEnd);
                const markEl = buildMarkEl
                    ? buildMarkEl(m, sliceText)
                    : document.createElement('mark');

                if (!buildMarkEl) {
                    markEl.textContent = sliceText;
                }
                frag.appendChild(markEl);
            }

            localCursor = Math.max(localCursor, relEnd);
        }

        if (localCursor < entry.text.length) {
            frag.appendChild(document.createTextNode(entry.text.slice(localCursor)));
        }

        entry.node.replaceWith(frag);
    }
}
