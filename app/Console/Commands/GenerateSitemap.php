<?php

namespace App\Console\Commands;

use App\Models\Post;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap including only the home page and published posts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting sitemap generation...');

        // Create a new sitemap instance
        $sitemap = Sitemap::create();

        // Add the home page
        $sitemap->add(
            Url::create('/')
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        $this->info('Added home page to sitemap.');

        // Fetch all published posts
        $posts = Post::where('published', true)->get();

        if ($posts->isEmpty()) {
            $this->warn('No published posts found.');
        } else {
            // Add each post to the sitemap
            foreach ($posts as $post) {
                $sitemap->add(
                    Url::create($post->custom_url)
                        ->setPriority(0.8)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                );
                $this->info("Added post: {$post->title}");
            }
        }

        // Define the path where the sitemap will be saved
        $sitemapPath = public_path('sitemap.xml');

        // Write the sitemap to the file
        try {
            $sitemap->writeToFile($sitemapPath);
            $this->info("Sitemap successfully generated at {$sitemapPath}.");
            Log::info("Sitemap successfully generated at {$sitemapPath}.");
        } catch (Exception $e) {
            $this->error("Failed to write sitemap to file: {$e->getMessage()}");
            Log::error("Failed to write sitemap to file: {$e->getMessage()}");

            // Optionally, send a notification email
            // Mail::to('admin@your-domain.com')->send(new SitemapGenerationFailed($e->getMessage()));

            return 1;
        }

        return 0;
    }
}
