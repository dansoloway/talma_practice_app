<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Display "TALMA Community Resources" for the default org.
     */
    public function up(): void
    {
        DB::table('organizations')
            ->where('slug', 'default')
            ->update(['name' => 'TALMA Community Resources']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('organizations')
            ->where('slug', 'default')
            ->update(['name' => 'Default']);
    }
};
