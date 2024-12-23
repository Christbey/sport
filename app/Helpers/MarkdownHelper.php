<?php

namespace App\Helpers;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Log;

class MarkdownHelper
{
    protected static $converter;

    public static function convert($markdown)
    {
        if (!self::$converter) {
            self::$converter = new CommonMarkConverter([
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
            ]);
        }

        // Create a unique cache key based on the Markdown content


        try {
            // Store in cache for 1 hour
            return self::$converter->convert($markdown)->getContent();
        } catch (CommonMarkException $e) {
            Log::error('Markdown conversion error: ' . $e->getMessage());
            return '<p>Error rendering content.</p>';
        }
    }
}
