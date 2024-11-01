<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use League\Csv\Writer;
use SplTempFileObject;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products {--pages=50}';
    protected $description = 'Scrape product details from Teachers Pay Teachers and save them to a CSV';

    protected $products = []; // Store scraped data

    public function handle()
    {
        $client = new Client();
        $baseUrl = 'https://www.teacherspayteachers.com/store/tara-west-little-minds-at-work';
        $totalPages = (int)$this->option('pages'); // Default to 50 pages

        for ($page = 1; $page <= $totalPages; $page++) {
            $this->info("Scraping page $page...");
            $url = $baseUrl . '?page=' . $page;

            try {
                $response = $client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    ],
                ]);
                $html = (string)$response->getBody();

                // Parse the HTML with DomCrawler
                $crawler = new Crawler($html);

                // Check if any products are found
                $products = $crawler->filter('.ProductRowLayout');
                if ($products->count() === 0) {
                    $this->error("No products found on page $page.");
                    continue;
                }

                $products->each(function (Crawler $node) {
                    // Safely extract product details
                    $title = $this->extractText($node, 'h2 a');
                    $price = $this->extractText($node, '.ProductPrice-module__stackedPrice--HDi24');
                    $seller = $this->extractText($node, '.ProductRowSellerByline-module__storeName--DZyfm');
                    $description = $this->extractText($node, '.ProductRowCard-module__cardDescription--jPu_8');
                    $image = $this->extractAttribute($node, '.ProductThumbnail-module__img--HRIPw', 'src');
                    $link = $this->extractAttribute($node, '.ProductRowCard-module__linkArea--aCqXC', 'href');

                    // Store product data in array
                    $this->products[] = [
                        'title' => $title,
                        'price' => $price,
                        'seller' => $seller,
                        'description' => $description,
                        'image' => $image,
                        'link' => "https://www.teacherspayteachers.com$link",
                    ];

                    // Print product information to console
                    $this->info("Product: $title");
                    $this->info("Price: $price");
                    $this->info("Seller: $seller");
                    $this->info("Description: $description");
                    $this->info("Image: $image");
                    $this->info("Link: https://www.teacherspayteachers.com$link");
                    $this->info('--------------------------------------------');
                });

                // Add a 7-second delay between page changes
                if ($page < $totalPages) {
                    $this->info('Waiting 7 seconds before scraping the next page...');
                    sleep(7); // Wait for 7 seconds
                }

            } catch (Exception $e) {
                $this->error("Error scraping page $page: " . $e->getMessage());
            }
        }

        // Save the scraped data to a CSV
        $this->saveToCsv();

        $this->info('Scraping completed and data saved to products.csv!');
    }

    /**
     * Extracts the text content from a node safely.
     */
    private function extractText(Crawler $node, string $selector): string
    {
        try {
            return $node->filter($selector)->text();
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Extracts the attribute value from a node safely.
     */
    private function extractAttribute(Crawler $node, string $selector, string $attribute): string
    {
        try {
            return $node->filter($selector)->attr($attribute);
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Saves the scraped data to a CSV file.
     */
    /**
     * Saves the scraped data to a CSV file.
     */
    /**
     * Saves the scraped data to a CSV file.
     */
    private function saveToCsv()
    {
        // Use League CSV Writer to create a CSV file at the correct path
        $csv = Writer::createFromPath(storage_path('products.csv'), 'w+');

        // Insert the header row
        $csv->insertOne(['Title', 'Price', 'Seller', 'Description', 'Image', 'Link']);

        // Insert each product's data
        foreach ($this->products as $product) {
            $csv->insertOne([
                $product['title'],
                $product['price'],
                $product['seller'],
                $product['description'],
                $product['image'],
                $product['link'],
            ]);
        }
    }
}
