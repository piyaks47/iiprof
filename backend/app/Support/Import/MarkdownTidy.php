<?php

namespace App\Support\Import;

/**
 * Cleans up Markdown converted from iiprof.com pages, which were typed in a rich-text editor:
 * whole paragraphs in bold, empty headings, headings in capitals, lines broken mid-sentence,
 * "Register Now" buttons that lost their link, and links to the old registration form.
 */
final class MarkdownTidy
{
    /** Words kept in capitals when a heading in capitals is rewritten. */
    private const ACRONYMS = ['IIP', 'CSR', 'NGO', 'CBO', 'MSME', 'IT', 'ITES', 'FDI', 'IPO', 'SHG', 'JLG', 'DSA', 'NBFC', 'R&D', 'EXIN', 'MBA', 'PHD', 'ITI', 'NRI', 'OJT', 'EDP', 'TPPM', 'India'];

    public static function clean(string $markdown): string
    {
        $md = str_replace("\r\n", "\n", $markdown);

        // Links to the old member registration form → this site's registration.
        $md = preg_replace('~\]\(https?://(?:www\.)?iiprof\.com/member/registration[^)\s]*\)~i', '](#register)', $md) ?? $md;

        // Bold markers glued together (**lea****dership**) and bold around images.
        $md = str_replace('****', '', $md);
        $md = preg_replace('/\*\*(!\[[^\]]*\]\([^)]+\))\*\*/', '$1', $md) ?? $md;

        $lines = explode("\n", $md);
        $out = [];
        foreach ($lines as $i => $line) {
            // Empty headings and headings that are only bold markers.
            if (preg_match('/^#{1,6}\s*(\*\*\s*\*\*)?\s*$/', $line)) {
                continue;
            }
            // "## **MISSION**" → "## Mission"
            if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
                $text = trim(preg_replace('/^\*\*(.+)\*\*$/', '$1', trim($m[2])) ?? $m[2]);
                $line = $m[1].' '.self::headingCase($text);
            } elseif (self::isPseudoHeading($line, $lines[$i - 1] ?? '', $lines[$i + 1] ?? '')) {
                // A short bold line on its own ("**Perspective**") was used as a heading.
                $line = '#### '.self::headingCase(preg_replace('/^([a-z]\))(?=\S)/u', '$1 ', trim(str_replace('**', '', $line))) ?? $line);
            } else {
                $line = self::unbold($line);
            }
            $out[] = $line;
        }
        $md = implode("\n", $out);

        // Hard line breaks in the middle of a sentence (text pasted from a PDF) → one paragraph.
        $md = preg_replace('/(?<=[\p{Ll},;\-–(])  \n(?=[\p{Ll}(])/u', ' ', $md) ?? $md;

        // "Want to be a Banks/NBFC member? Register Now" — the button lost its link.
        $md = preg_replace('/^(Want to be an? .+? member\?)\s*Register Now\s*$/mu', '$1 [Register now](#register)', $md) ?? $md;
        // A lone "Book My Seat" / "Register Now" button at the end.
        $md = preg_replace('/\n\n(Book My Seat)\s*$/u', "\n\n[Book my seat](#enquiry)", $md) ?? $md;
        $md = preg_replace('/\n\n(Register Now|Join Now)\s*$/iu', "\n\n[Register now](#register)", $md) ?? $md;

        // A one-row table whose cells are lists ("| - a - b | - c - d |") → one list.
        $md = preg_replace_callback('/^\|(.+)\|\n\|(?: *-{3,} *\|)+\n?(?!\|)/m', function (array $m): string {
            $cells = array_map('trim', explode('|', $m[1]));
            if (! $cells || array_filter($cells, fn ($c) => $c !== '' && ! str_starts_with($c, '- '))) {
                return $m[0];
            }
            $items = [];
            foreach ($cells as $cell) {
                foreach (preg_split('/(?:^|\s)- /', $cell) ?: [] as $item) {
                    if (trim($item) !== '') {
                        $items[] = '- '.trim($item);
                    }
                }
            }

            return implode("\n", $items)."\n\n";
        }, $md) ?? $md;

        $md = preg_replace("/\n{3,}/", "\n\n", $md) ?? $md;

        return trim($md);
    }

    private static function isPseudoHeading(string $line, string $before, string $after): bool
    {
        $text = trim($line);

        return trim($before) === '' && trim($after) === ''
            && preg_match('/^\*\*([^*]{2,80})\*\*$/u', $text, $m) === 1
            && ! preg_match('/[.,;]$/u', trim($m[1]));
    }

    /** A paragraph or list item that is bold from start to end is shown as normal text. */
    private static function unbold(string $line): string
    {
        if (! preg_match('/^(\s*(?:[-*]|\d+\.)\s+|\s*)(.*)$/u', $line, $m)) {
            return $line;
        }
        [$prefix, $text] = [$m[1], $m[2]];
        $trimmed = trim($text);

        if (preg_match('/^\*\*(.+)\*\*(\s{2})?$/u', $trimmed, $b) && ! str_contains($b[1], '**')) {
            return $prefix.$b[1].($b[2] ?? '');
        }
        // "**part one** **part two**" (a bold paragraph split by the editor)
        if (preg_match('/^(\*\*[^*]+\*\*\s*){2,}$/u', $trimmed)) {
            return $prefix.str_replace('**', '', $trimmed);
        }

        return $line;
    }

    /** "KNOW IIP" → "Know IIP"; headings in mixed case are kept. */
    public static function headingCase(string $text): string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $text) ?? '';
        if ($letters === '' || mb_strtoupper($letters) !== $letters || mb_strlen($letters) < 5) {
            return $text;
        }

        $words = preg_split('/(\s+)/u', mb_strtolower($text), -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        foreach ($words as $i => $word) {
            $bare = mb_strtoupper(preg_replace("/[’']s$/u", '', $word) ?? $word);
            foreach (self::ACRONYMS as $acronym) {
                if ($bare === mb_strtoupper($acronym)) {
                    $words[$i] = $acronym.mb_substr($word, mb_strlen($acronym));
                }
            }
        }
        $text = implode('', $words);

        return mb_strtoupper(mb_substr($text, 0, 1)).mb_substr($text, 1);
    }
}
