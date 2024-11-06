<?php

namespace App\Console\Commands;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use League\Csv\Writer;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products {--pages=3}';
    protected $description = 'Scrape product details from Teachers Pay Teachers and save them to a CSV';

    protected $products = []; // Store scraped data

    public function handle()
    {
        $client = new Client();
        $baseUrl = 'https://www.teacherspayteachers.com/store/tara-west-little-minds-at-work';
        $totalPages = (int)$this->option('pages');

        // Primary Scraping: Gather initial data
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
                $crawler = new Crawler($html);

                // Check if any products are found
                $products = $crawler->filter('.ProductRowCard-module__card--xTOd6');
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

                    // Store product data in array with a placeholder for additional details
                    $this->products[] = [
                        'title' => $title,
                        'price' => $price,
                        'seller' => $seller,
                        'description' => $description,
                        'image' => $image,
                        'link' => "https://www.teacherspayteachers.com$link",
                        'detailed_description' => '',  // Placeholder for secondary scraping
                        'preview_images' => '',        // Placeholder for secondary scraping
                    ];

                    $this->info("Scraped product: $title");
                });

                if ($page < $totalPages) {
                    $this->info('Waiting 7 seconds before scraping the next page...');
                    sleep(7);
                }

            } catch (Exception $e) {
                $this->error("Error scraping page $page: " . $e->getMessage());
            }
        }

        // Secondary Scraping: Visit each product link to gather additional details
        foreach ($this->products as &$product) {
            $this->info('Scraping additional details for: ' . $product['title']);
            try {
                $response = $client->request('GET', $product['link'], [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    ],
                ]);

                $html = (string)$response->getBody();
                $crawler = new Crawler($html);

                // Extract Detailed Description
                $detailedDescription = $this->extractText($crawler, '.ProductDescriptionLayout__htmlDisplay--fromNewEditor');
                $product['detailed_description'] = $detailedDescription;

                // Extract Preview Images
                $imageUrls = [];
                $crawler->filter('.ProductPreviewSlider__slidesContainer img')->each(function (Crawler $imageNode) use (&$imageUrls) {
                    $imageUrls[] = $imageNode->attr('src');
                });
                $product['preview_images'] = implode(', ', $imageUrls); // Join image URLs as comma-separated string

                $this->info('Detailed description and images scraped for: ' . $product['title']);
                sleep(2);

            } catch (Exception $e) {
                $this->error('Error scraping additional details for: ' . $product['title'] . ' - ' . $e->getMessage());
            }
        }

        // Save all scraped data to a CSV file
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
            $this->error("Error extracting text for selector $selector: " . $e->getMessage());
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
            $this->error("Error extracting attribute '$attribute' for selector $selector: " . $e->getMessage());
            return 'N/A';
        }
    }

    /**
     * Saves the scraped data to a CSV file.
     */
    private function saveToCsv()
    {
        try {
            $csv = Writer::createFromPath(storage_path('products.csv'), 'w+');

            // Insert the header row
            $csv->insertOne(['Title', 'Price', 'Seller', 'Description', 'Image', 'Link', 'Detailed Description', 'Preview Images']);

            // Insert each product's data
            foreach ($this->products as $product) {
                $csv->insertOne([
                    $product['title'],
                    $product['price'],
                    $product['seller'],
                    $product['description'],
                    $product['image'],
                    $product['link'],
                    $product['detailed_description'] ?? 'N/A',
                    $product['preview_images'] ?? 'N/A',
                ]);
            }

            $this->info('Data successfully written to products.csv');
        } catch (Exception $e) {
            $this->error('Error saving CSV: ' . $e->getMessage());
        }
    }
}