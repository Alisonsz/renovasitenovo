<?php

namespace App\Support;

class HtmlSanitizer
{
    /**
     * Allow only safe, formatting-oriented tags from the admin rich-text editor.
     * Removes <script>/<style>, inline event handlers (on*), and javascript: URLs.
     * Good enough for admin-authored content rendered via v-html.
     */
    public static function clean(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        // Drop entire dangerous elements (with their content).
        $html = preg_replace('#<(script|style|iframe|object|embed|form)\b[^>]*>.*?</\1>#is', '', $html);

        // Strip everything except an allowlist of formatting tags. Includes media
        // tags so imported WordPress blog content keeps its images/figures.
        $allowed = '<p><br><strong><b><em><i><u><h1><h2><h3><h4><h5><ul><ol><li>'
            .'<a><blockquote><span><img><figure><figcaption><hr><pre><code><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowed);

        // Remove inline event handlers (onclick=, onerror=, …).
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Neutralize javascript: / data: hrefs.
        $html = preg_replace('/(href|src)\s*=\s*("|\')\s*(javascript|data):[^"\']*\2/i', '$1=$2#$2', $html);

        return trim($html);
    }
}
