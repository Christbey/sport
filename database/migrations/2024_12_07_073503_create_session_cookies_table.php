<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('session_cookies', function (Blueprint $table) {
            $table->id();
            // We'll use a string for the 13-digit unique identifier.
            // You could also index this for fast lookups.
            $table->string('unique_id', 13)->unique();

            // Foreign key to users table, nullable if user not known.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('ip_v4', 45)->nullable();
            $table->string('ip_v6', 45)->nullable();

            // Using text for user_agent as it can be long
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('session_cookies');
    }
};
