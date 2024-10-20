<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserIdToCollegeFootballNotesTable extends Migration
{
    public function up()
    {
        Schema::table('college_football_notes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->onDelete('cascade');
        });

        // Optionally, assign a default user_id to existing records
        DB::table('college_football_notes')->update(['user_id' => 1]); // Adjust 1 to the appropriate user ID
    }

    public function down()
    {
        Schema::table('college_football_notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}
