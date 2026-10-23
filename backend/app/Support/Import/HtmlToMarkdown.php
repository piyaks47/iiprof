<?php

namespace App\Support\Import;

use DOMElement;
use DOMNode;
use DOMText;

/**
 * Converts the HTML of a page (headings, paragraphs, lists, tables, links, images, bold/italic)
 * into the Markdown the page editor uses. Layout wrappers are flattened, scripts, forms and
 * buttons are dropped, and relative links / images are made absolute against $base.
 */
class HtmlToMarkdown
{
    private const SKIP = ['script', 'style', 'noscript', 'form', 'button', 'input', 'select', 'textarea', 'label',
        'svg', 'canvas', 'iframe', 'video', 'audio', 'object', 'embed', 'template', 'nav', 'head', 'meta', 'link'];

    private const BLOCKS = ['p', 'div', 'section', 'article', 'main', 'aside', 'header', 'footer', 'figure', 'figcaption',
        'address', 'center', 'dl', 'dt', 'dd'];

    /** Placeholder for <br> until the end (trailing spaces are significant there). */
    private const BR = "\x1E";

    public function __construct(private string $base = '') {}

    public function convert(DOMNode $root): string
    {
        $markdown = $this->children($root);

        // Tidy: one <br> at a time and none next to a paragraph break, no trailing spaces, at most one blank line.
        $br = self::BR;
        $markdown = preg_replace("/ *{$br}[{$br} ]*/", $br, $markdown) ?? '';
        $markdown = preg_replace("/{$br}(?=[ \t]*\n)|(?<=\n)[ \t]*{$br}/", '', $markdown) ?? '';
        $markdown = preg_replace('/[ \t]+$/m', '', $markdown) ?? '';
        $markdown = preg_replace("/\n{3,}/", "\n\n", $markdown) ?? '';
        $markdown = trim($markdown, " \n{$br}");

        return str_replace(self::BR, "  \n", $markdown);
    }

    private function children(DOMNode $node): string
    {
        $out = '';
        foreach ($node->childNodes as $child) {
            $out .= $this->node($child);
        }

        return $out;
    }

    private function node(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return $this->text($node->wholeText);
        }
        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::SKIP, true) || $this->hidden($node)) {
            return '';
        }

        return match ($tag) {
            'h1', 'h2' => $this->block('## '.$this->inline($node)),
            'h3' => $this->block('### '.$this->inline($node)),
            'h4', 'h5', 'h6' => $this->block('#### '.$this->inline($node)),
            'br' => self::BR,
            'hr' => $this->block('---'),
            'strong', 'b' => $this->wrap($node, '**'),
            'em', 'i' => $this->wrap($node, '*'),
            'a' => $this->link($node),
            'img' => $this->image($node),
            'ul', 'ol' => $this->block($this->list($node, $tag === 'ol', 0)),
            'blockquote' => $this->block(preg_replace('/^/m', '> ', trim($this->squash($this->children($node)))) ?? ''),
            'table' => $this->block($this->table($node)),
            'pre' => $this->block("```\n".trim($node->textContent)."\n```"),
            'code' => '`'.trim($node->textContent).'`',
            default => in_array($tag, self::BLOCKS, true) ? $this->block($this->children($node)) : $this->children($node),
        };
    }

    private function block(string $content): string
    {
        $content = trim($this->squash($content), " \n".self::BR);

        return $content === '' ? '' : "\n\n".$content."\n\n";
    }

    /** One-line content of an element (headings, list items, table cells). */
    private function inline(DOMNode $node): string
    {
        return trim(preg_replace('/\s*[\n'.self::BR.']\s*/', ' ', $this->children($node)) ?? '');
    }

    private function wrap(DOMElement $node, string $mark): string
    {
        $inner = $this->children($node);
        $text = trim($inner);
        if ($text === '' || str_contains($text, "\n\n")) {
            return $inner; // icon fonts (<i class="fa …">) or block content: no emphasis
        }
        $lead = preg_match('/^\s/', $inner) ? ' ' : '';
        $tail = preg_match('/\s$/', $inner) ? ' ' : '';

        return $lead.$mark.$text.$mark.$tail;
    }

    private function link(DOMElement $node): string
    {
        $inner = $this->children($node);
        $text = trim($inner);
        $href = trim($node->getAttribute('href'));
        $lead = preg_match('/^\s/', $inner) ? ' ' : '';
        $tail = preg_match('/\s$/', $inner) ? ' ' : '';

        if ($text === '' || str_contains($text, '![') || str_contains($text, "\n\n")) {
            return $inner; // empty, a linked image or a linked block: keep the content
        }
        if ($href === '' || preg_match('~^(javascript:|#$)~i', $href)) {
            return $lead.$text.$tail;
        }

        return $lead.'['.$text.']('.$this->url($href).')'.$tail;
    }

    private function image(DOMElement $node): string
    {
        $src = trim($node->getAttribute('data-src') ?: $node->getAttribute('src'));
        if ($src === '' || str_starts_with($src, 'data:')) {
            return '';
        }
        $alt = trim(preg_replace('/[\[\]\s]+/', ' ', $node->getAttribute('alt')) ?? '');

        return "\n\n![".$alt.']('.$this->url($src).")\n\n";
    }

    private function list(DOMElement $list, bool $ordered, int $depth): string
    {
        $lines = [];
        $n = 1;
        foreach ($list->childNodes as $item) {
            if (! $item instanceof DOMElement || strtolower($item->tagName) !== 'li') {
                continue;
            }
            $nested = '';
            $content = '';
            foreach ($item->childNodes as $child) {
                if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol'], true)) {
                    $nested .= "\n".$this->list($child, strtolower($child->tagName) === 'ol', $depth + 1);
                } else {
                    $content .= $this->node($child);
                }
            }
            $content = trim(preg_replace('/\s*[\n'.self::BR.']\s*/', ' ', $this->squash($content)) ?? '');
            if ($content === '' && $nested === '') {
                continue;
            }
            $lines[] = str_repeat('   ', $depth).($ordered ? ($n++).'. ' : '- ').$content.$nested;
        }

        return implode("\n", $lines);
    }

    private function table(DOMElement $table): string
    {
        $rows = [];
        foreach ($table->getElementsByTagName('tr') as $tr) {
            $cells = [];
            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    $cells[] = str_replace('|', '\|', $this->inline($cell));
                }
            }
            if ($cells) {
                $rows[] = $cells;
            }
        }
        if (! $rows) {
            return '';
        }

        $width = max(array_map('count', $rows));
        $line = fn (array $cells) => '| '.implode(' | ', array_pad($cells, $width, '')).' |';
        $out = [$line($rows[0]), '| '.implode(' | ', array_fill(0, $width, '---')).' |'];
        foreach (array_slice($rows, 1) as $row) {
            $out[] = $line($row);
        }

        return implode("\n", $out);
    }

    private function text(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $text)) ?? '';

        return preg_replace('/([\\\\`*_\[\]])/', '\\\\$1', $text) ?? $text;
    }

    /** Collapses runs of spaces left by flattened wrappers (outside line breaks). */
    private function squash(string $markdown): string
    {
        $markdown = preg_replace('/[ \t]*\n[ \t]*\n[ \t\n]*/', "\n\n", $markdown) ?? $markdown;

        return preg_replace('/(?<=\S) {2,}(?=\S)/', ' ', $markdown) ?? $markdown;
    }

    private function hidden(DOMElement $node): bool
    {
        $class = ' '.strtolower($node->getAttribute('class')).' ';
        $style = strtolower(str_replace(' ', '', $node->getAttribute('style')));

        return $node->hasAttribute('hidden')
            || str_contains($style, 'display:none')
            || preg_match('/ (modal|d-none|sr-only|visually-hidden|preloader) /', $class) === 1;
    }

    public function url(string $href): string
    {
        $href = html_entity_decode($href, ENT_QUOTES | ENT_HTML5);
        if (preg_match('~^(mailto:|tel:|#)~i', $href)) {
            return str_replace(' ', '%20', $href);
        }

        return self::absolute($href, $this->base);
    }

    /** Absolute URL with spaces and brackets encoded (safe inside Markdown links). */
    public static function absolute(string $href, string $base): string
    {
        if (str_starts_with($href, '//')) {
            $href = 'https:'.$href;
        } elseif (! preg_match('~^[a-z][a-z0-9+.-]*:~i', $href) && $base !== '') {
            $parts = parse_url($base);
            $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');
            if (str_starts_with($href, '/')) {
                $href = $origin.$href;
            } else {
                // iiprof.com uses document-relative paths ("public/uploads/…") that resolve from the site root.
                $dir = preg_replace('~/[^/]*$~', '/', $parts['path'] ?? '/') ?: '/';
                $href = $origin.(str_starts_with($href, 'public/') ? '/' : $dir).$href;
            }
        }

        return strtr($href, [' ' => '%20', '(' => '%28', ')' => '%29', '[' => '%5B', ']' => '%5D']);
    }
}
