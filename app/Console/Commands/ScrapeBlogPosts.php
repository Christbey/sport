<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeBlogPosts extends Command
{
    protected $signature = 'scrape:blog';
    protected $description = 'Scrape articles from blog and export title, content, and images in a CSV format';
    protected $client;
    protected $csvFilePath;

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
        $this->csvFilePath = storage_path('app/scraped_blog_posts.csv'); // CSV file path
    }

    public function handle()
    {
        $baseUrl = 'https://littlemindsatwork.org/blog/page/';
        $totalPages = 63;

        // Initialize the CSV file with headers
        $this->initializeCsv();

        for ($page = 1; $page <= $totalPages; $page++) {
            $url = $baseUrl . $page . '/';
            $response = $this->client->get($url);
            $html = (string)$response->getBody();

            $crawler = new Crawler($html);
            $articles = $crawler->filter('article.entry');

            $articles->each(function (Crawler $node) {
                // Fetch title and link
                $title = $node->filter('h2.entry-title a')->count() ? $node->filter('h2.entry-title a')->text() : 'No title';
                $link = $node->filter('h2.entry-title a')->count() ? $node->filter('h2.entry-title a')->attr('href') : null;

                // If a link is available, fetch the full content
                if ($link) {
                    $this->fetchAndExportContent($title, $link);
                }
            });
        }
    }

    protected function initializeCsv()
    {
        // Open the CSV file for writing, place headers if it's empty
        $file = fopen($this->csvFilePath, 'w');
        fputcsv($file, ['Title', 'Content', 'Images']); // CSV headers
        fclose($file);
    }

    protected function fetchAndExportContent($title, $url)
    {
        $response = $this->client->get($url);
        $html = (string)$response->getBody();

        $crawler = new Crawler($html);

        // Fetch and clean the main content of the article
        $content = $crawler->filter('.entry-content.single-content')->count() ?
            $crawler->filter('.entry-content.single-content')->html() : 'No content';

        $cleanContent = $this->cleanContent($content);

        // Fetch all images within the content and join them as a single string
        $images = $crawler->filter('.entry-content.single-content img')->each(function (Crawler $imgNode) {
            return $imgNode->attr('src');
        });
        $imageList = implode(', ', $images); // Concatenate image URLs as a single string

        // Append to CSV
        $this->appendToCsv($title, $cleanContent, $imageList);
    }

    protected function cleanContent($content)
    {
        $content = strip_tags($content, '<p><br>');
        $content = preg_replace('/&nbsp;|&#160;|\s+/', ' ', $content);
        $content = preg_replace('/\s{2,}/', ' ', $content);
        return trim($content);
    }

    protected function appendToCsv($title, $content, $images)
    {
        // Open the CSV file in append mode and add each row
        $file = fopen($this->csvFilePath, 'a');
        fputcsv($file, [$title, $content, $images]);
        fclose($file);
    }
}
