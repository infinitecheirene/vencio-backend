<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('full_description');
            $table->decimal('price', 10, 2);
            $table->integer('capacity');
            $table->string('size');
            $table->string('bed_type');
            $table->json('amenities');
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('panoramas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};