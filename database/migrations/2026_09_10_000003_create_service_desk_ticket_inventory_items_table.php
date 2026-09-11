<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_desk_ticket_inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('service_desk_tickets')->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained('service_desk_inventory_items')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->timestamps();

            $table->unique(['ticket_id', 'inventory_item_id'], 'sd_ticket_inventory_items_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_desk_ticket_inventory_items');
    }
};
