<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('openai_completions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();

            // The completion ID returned by OpenAI (e.g. "chatcmpl-123456")
            $table->string('completion_id')->nullable()->index();

            // The object type (e.g. "chat.completion")
            $table->string('object')->nullable();

            // The timestamp from OpenAI. This is a Unix timestamp, so we may convert it later.
            // We'll store it as a timestamp. If you need the original integer,
            // you could store that as well. For now, let's store the converted datetime:
            $table->timestamp('openai_created_at')->nullable();

            // The model used (e.g. "gpt-4o-2024-08-06")
            $table->string('model')->nullable();

            // The system_fingerprint (e.g. "fp_6b68a8204b")
            $table->string('system_fingerprint')->nullable();

            // The choices array returned by OpenAI, which can contain multiple messages.
            // Store as JSON:
            $table->json('choices')->nullable();

            // Usage details, also JSON since it contains various counts and nested details.
            $table->json('usage')->nullable();

            // Optional: if you have additional metadata or tools from previous structures,
            // you can keep these or remove them if not needed:
            $table->json('metadata')->nullable();
            $table->json('tools')->nullable();

            // The original request messages sent to OpenAI (if you store them).
            $table->json('messages')->nullable();

            // If you'd like to store the final assistant message separately,
            // you can still do so. However, it's contained within choices, so it may not be needed.
            $table->json('response_messages')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('openai_completions');
    }
};