<?php

namespace App\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\View\View;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;
use Log;

class OpenAI
{

    public static function buildConversationMessages(int $currentWeek, string $today, string $userMessage): array
    {
        return array_merge(
            self::buildSystemMessages($currentWeek, $today),
            [['role' => 'user', 'content' => $userMessage]]
        );
    }

    /**
     * Build the system messages for the OpenAI prompt.
     */
    public static function buildSystemMessages(int $currentWeek, string $today): array
    {
        return [
            [
                'role' => 'system',
                'content' => "Today's date is {$today}. The NFL regular season started on September 7, 2023. We are currently in Week {$currentWeek}."
            ],
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant who can dynamically fetch NFL predictions.'
            ]
        ];
    }

    /**
     * Get the current NFL week based on the config and today's date.
     */
    public static function getCurrentNFLWeek(): int
    {
        $today = Carbon::today();
        $weeks = config('nfl.weeks');

        foreach ($weeks as $week => $dateRange) {
            $startDate = Carbon::parse($dateRange['start']);
            $endDate = Carbon::parse($dateRange['end']);

            if ($today->between($startDate, $endDate)) {
                return (int)$week;
            }
        }

        // Default to week 1 if no match is found
        return 1;
    }

    /**
     * Parse user input and adjust arguments based on natural language references.
     */
    public static function parseArguments(array $arguments, string $userMessage, int $currentWeek): array
    {
        if (str_contains($userMessage, 'last week')) {
            $arguments['week'] = $currentWeek - 1;
        }

        if (preg_match('/over the last (\d+) weeks/', $userMessage, $matches)) {
            $weeksBack = (int)$matches[1];
            $arguments['start_week'] = max(1, $currentWeek - $weeksBack);
            $arguments['end_week'] = $currentWeek - 1;
        }

        return $arguments;
    }

    public static function determineFunctionAndArguments(string $userMessage, int $currentWeek): array
    {
        $functionName = 'get_schedule_by_team';
        $arguments = [];

        // Handle specific queries
        if (str_contains($userMessage, 'this weekend')) {
            $arguments['query'] = 'this weekend';
            $functionName = 'get_schedule_by_date_range';
        } elseif (str_contains($userMessage, 'yesterday')) {
            $arguments['query'] = 'yesterday';
            $functionName = 'get_schedule_by_date_range';
        } elseif (str_contains($userMessage, 'last week')) {
            $arguments['query'] = 'last week';
            $functionName = 'get_schedule_by_date_range';
        } elseif (str_contains($userMessage, 'Christmas')) {
            $arguments['query'] = 'Christmas';
            $functionName = 'get_schedule_by_date_range';
        } elseif (preg_match('/week (\d+)/', $userMessage, $matches)) {
            $arguments['week'] = (int)$matches[1];
            $functionName = 'get_predictions_by_week';
        }

        // Default fallback
        return [
            'functionName' => $functionName,
            'arguments' => $arguments,
        ];
    }

    public static function validateResponseContent(?array $response): string
    {
        if (empty($response['choices'][0]['message']['content'])) {
            throw new Exception('Response content is null or missing.');
        }

        return $response['choices'][0]['message']['content'];
    }

    public static function handleException(Exception $e, ?array $response = null): View
    {
        Log::error('Error processing chat request', [
            'exception' => $e->getMessage(),
            'response' => $response,
        ]);

        return view('openai.index', ['response' => 'An error occurred: ' . $e->getMessage()]);
    }


    /**
     * Convert Markdown content to HTML.
     */
    /**
     * Convert Markdown content to HTML.
     */
    public static function convertMarkdownToHtml(?string $markdown): string
    {
        // Handle null input
        if ($markdown === null) {
            return '';
        }

        // Trim input
        $markdown = trim($markdown);
        if (empty($markdown)) {
            return '';
        }

        // Check if the content contains a table structure
        if (preg_match('/^\|.+\|$/m', $markdown)) {
            // Handle table-formatted data
            $lines = explode("\n", $markdown);
            $output = '';
            $headers = [];
            $isFirstRow = true;

            foreach ($lines as $line) {
                $cleanedLine = strip_tags($line);

                if (preg_match('/^\|(.+)\|$/', $cleanedLine, $matches)) {
                    $cells = array_map('trim', explode('|', $matches[1]));
                    $cells = array_filter($cells);

                    if ($isFirstRow) {
                        $headers = $cells;
                        $isFirstRow = false;
                        continue;
                    }

                    // Skip separator rows
                    if (preg_match('/^[\s\-\|]+$/', $cleanedLine)) {
                        continue;
                    }

                    // Only process rows that have the same number of columns as headers
                    if (!empty($cells) && count($cells) === count($headers)) {
                        $stats = array_combine($headers, $cells);
                        $formattedStats = [];

                        foreach ($stats as $key => $value) {
                            // Bold numerical values
                            if (is_numeric(str_replace(['%', ',', '.'], '', $value))) {
                                $formattedStats[] = "$key: **$value**";
                            } else {
                                $formattedStats[] = "$key: $value";
                            }
                        }

                        $output .= '* ' . implode(', ', $formattedStats) . "\n";
                    }
                } else {
                    // Keep non-table content
                    if (trim($cleanedLine) !== '') {
                        $output .= $cleanedLine . "\n";
                    }
                }
            }

            $markdown = $output;
        }

        // Convert final markdown to HTML
        try {
            $converter = new CommonMarkConverter();
            return $converter->convert($markdown)->getContent();
        } catch (CommonMarkException $e) {
            Log::error('Markdown conversion failed', [
                'exception' => $e,
                'markdown' => $markdown,
            ]);
            return 'An error occurred while processing the response.';
        }
    }
}
