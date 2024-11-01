<?php

namespace App\Providers;

use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\Discord\Discord;
use NotificationChannels\Discord\DiscordChannel;
use GuzzleHttp\Client as HttpClient;

class NotificationServiceProvider extends ServiceProvider
{

    public function boot()
    {
        // Bind the Discord instance with token
        $this->app->singleton(Discord::class, function ($app) {
            $httpClient = new HttpClient();
            $token = config('services.discord.token');
            return new Discord($httpClient, $token);
        });

        // Register the DiscordChannel
        $this->app->make(ChannelManager::class)->extend('discord', function ($app) {
            return new DiscordChannel($app->make(Discord::class));
        });
    }

}
