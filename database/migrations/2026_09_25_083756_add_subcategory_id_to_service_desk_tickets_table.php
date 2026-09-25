<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_desk_tickets', function (Blueprint $table) {
            $table->foreignId('subcategory_id')
                ->nullable()
                ->after('category_id')
                ->constrained('service_desk_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('service_desk_tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subcategory_id');
        });
    }
};
