<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('capacity');
            $table->string('size');
            $table->string('bed_type');
            $table->text('description');
            $table->json('amenities');
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('panoramas')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rooms');
    }
}
