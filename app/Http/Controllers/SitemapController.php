<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function generate()
    {
        $sitemap = Sitemap::create();

        // Add the home page
        $sitemap->add(
            Url::create('/')
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        // Add all published posts
        Post::where('published', true)->get()->each(function (Post $post) use ($sitemap) {
            $sitemap->add(
                Url::create($post->custom_url)
                    ->setPriority(0.8)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            );
        });

        // Define the path where the sitemap will be saved
        $sitemapPath = public_path('sitemap.xml');

        // Write the sitemap to the file
        $sitemap->writeToFile($sitemapPath);

        return response()->json(['message' => 'Sitemap successfully generated.']);
    }
}
