<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\CollegeFootball\CollegeFootballNote;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;

    // Removed duplicate Notifiable

    use TwoFactorAuthenticatable;
    use HasRoles;
    use Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'card_brand',
        'card_last_four',
        'trial_ends_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Route notifications for the Discord channel.
     *
     * @return string
     */
    public function routeNotificationForDiscord(): string
    {
        return config('services.discord.channel_id'); // Discord channel ID
    }

    /**
     * Get the customer's name for Stripe.
     *
     * @return string
     */
    public function stripeName()
    {
        return $this->name;
    }

    /**
     * Get the customer email that should be synced to Stripe.
     *
     * @return string
     */
    public function stripeEmail()
    {
        return $this->email;
    }

    /**
     * Check if the user has an active subscription.
     *
     * @return bool
     */
    public function hasActiveSubscription()
    {
        return $this->subscribed('default');
    }

    /**
     * Get the name of the active subscription's plan.
     *
     * @return string|null
     */
    /**
     * Get the name of the active subscription's plan.
     *
     * @return string|null
     */
    public function activeSubscriptionPlanName()
    {
        $activeSubscription = $this->activeSubscription();
        return optional($activeSubscription->plan)->name;
    }


    /**
     * Get the active subscription.
     *
     * @return \Laravel\Cashier\Subscription|null
     */
    public function activeSubscription()
    {
        return $this->subscription('default');
    }

    /**
     * Determine the chat limit based on the subscription plan.
     *
     * @return int
     */
    public function getChatLimit(): int
    {
        if ($this->activeSubscription()?->hasPrice('price_premium')) {
            return 100; // Premium tier
        } elseif ($this->activeSubscription()?->hasPrice('price_standard')) {
            return 60;  // Standard tier
        }
        return 5;      // Free tier
    }

    /**
     * Get all submissions made by the user.
     */
    public function submissions()
    {
        return $this->hasMany(UserSubmission::class);
    }

    /**
     * Get all notes associated with the user.
     */
    public function notes()
    {
        return $this->hasMany(CollegeFootballNote::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


}
