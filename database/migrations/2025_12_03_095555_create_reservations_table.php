<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number')->unique();
            $table->foreignId('venue_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', ['single', 'multi']);
            $table->date('event_date')->nullable();
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->integer('nights')->default(0);
            $table->integer('attendees');
            $table->boolean('needs_rooms')->default(false);
            
            // Contact Information
            $table->string('organization');
            $table->string('event_name');
            $table->string('contact_person');
            $table->string('position')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->text('details')->nullable();
            
            // Pricing
            $table->decimal('venue_total', 10, 2);
            $table->decimal('rooms_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            
            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}