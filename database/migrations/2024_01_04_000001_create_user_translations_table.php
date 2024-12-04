<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTranslationsTable extends Migration
{
    public function up()
    {
        Schema::create('user_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('locale');
            $table->text('bio')->nullable();
            $table->string('address')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'locale']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_translations');
    }
}
