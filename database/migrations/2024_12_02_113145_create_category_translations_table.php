<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip creating category_translations table as it's already created in previous migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip dropping category_translations table as it will be dropped in previous migration
    }
};