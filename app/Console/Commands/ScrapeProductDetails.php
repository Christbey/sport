<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Symfony\Component\DomCrawler\Crawler;
use League\Csv\Writer;
use SplTempFileObject;

class ScrapeProductDetails extends Command
{
    protected $signature = 'scrape:product-details {--pages=50}';
    protected $description = 'Scrape product details from Teachers Pay Teachers and save to product_details.csv';

    protected $products = []; // Store scraped products
    protected $client;         // Guzzle client

    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    public function handle()
    {
        $this->scrapeProducts();   // Scrape product list
        $this->scrapeProductDetails(); // Scrape individual product descriptions
        $this->info('Scraping completed successfully!');
    }

    /**
     * Scrape the product list and save the basic data.
     */
    private function scrapeProducts()
    {
        $baseUrl = 'https://www.teacherspayteachers.com/store/tara-west-little-minds-at-work';
        $totalPages = (int)$this->option('pages');

        for ($page = 1; $page <= $totalPages; $page++) {
            $this->info("Scraping page $page...");
            $url = $baseUrl . '?page=' . $page;

            try {
                $response = $this->client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    ],
                ]);

                $html = (string)$response->getBody();
                $crawler = new Crawler($html);

                $crawler->filter('.ProductRowLayout')->each(function (Crawler $node) {
                    $title = $this->extractText($node, 'h2 a');
                    $price = $this->extractText($node, '.ProductPrice-module__stackedPrice--HDi24');
                    $seller = $this->extractText($node, '.ProductRowSellerByline-module__storeName--DZyfm');
                    $link = $this->extractAttribute($node, '.ProductRowCard-module__linkArea--aCqXC', 'href');

                    $this->products[] = [
                        'title' => $title,
                        'price' => $price,
                        'seller' => $seller,
                        'link' => "https://www.teacherspayteachers.com$link",
                    ];
                });

                if ($page < $totalPages) {
                    $this->info('Waiting 7 seconds before the next page...');
                    sleep(7);
                }
            } catch (Exception $e) {
                $this->error("Error on page $page: " . $e->getMessage());
            }
        }

        $this->saveProductsCsv(); // Save initial product data
    }

    /**
     * Extracts text content from a node safely.
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
     * Extracts an attribute value from a node safely.
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
     * Save the product list to a CSV file.
     */
    private function saveProductsCsv()
    {
        $csv = Writer::createFromPath(storage_path('products.csv'), 'w+');
        $csv->insertOne(['Title', 'Price', 'Seller', 'Link']);

        foreach ($this->products as $product) {
            $csv->insertOne([
                $product['title'],
                $product['price'],
                $product['seller'],
                $product['link'],
            ]);
        }
    }

    /**
     * Scrape the product description from each product page.
     */
    private function scrapeProductDetails()
    {
        $details = [];

        foreach ($this->products as $product) {
            $this->info('Scraping details for: ' . $product['title']);

            try {
                $response = $this->client->request('GET', $product['link'], [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    ],
                ]);

                $html = (string)$response->getBody();
                $crawler = new Crawler($html);

                $description = $crawler
                    ->filter('.ProductDescriptionLayout__htmlDisplay--fromNewEditor')
                    ->text();

                $details[] = [
                    'title' => $product['title'],
                    'price' => $product['price'],
                    'seller' => $product['seller'],
                    'link' => $product['link'],
                    'description' => $description,
                ];

                $this->info('Successfully scraped details for: ' . $product['title']);
            } catch (Exception $e) {
                $this->error('Error scraping details for: ' . $product['title']);
            }

            sleep(2); // Add a small delay between product requests
        }

        $this->saveDetailsCsv($details); // Save product details to CSV
    }

    /**
     * Save the product details to a CSV file.
     */
    private function saveDetailsCsv(array $details)
    {
        $csv = Writer::createFromPath(storage_path('product_details.csv'), 'w+');
        $csv->insertOne(['Title', 'Price', 'Seller', 'Link', 'Description']);

        foreach ($details as $detail) {
            $csv->insertOne([
                $detail['title'],
                $detail['price'],
                $detail['seller'],
                $detail['link'],
                $detail['description'],
            ]);
        }
    }
}
