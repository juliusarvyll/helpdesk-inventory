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
        Schema::table('service_desk_inventory_items', function (Blueprint $table) {
            $table->text('serial_number')->nullable()->change();
            $table->string('asset_tag')->nullable()->unique()->after('created_by_id');
            $table->string('category')->nullable()->after('type');
            $table->string('status')->default('available')->after('description');
            $table->string('location')->nullable()->after('unit');
            $table->date('purchased_at')->nullable()->after('location');
            $table->date('warranty_expires_at')->nullable()->after('purchased_at');
            $table->json('metadata')->nullable()->after('warranty_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_desk_inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'asset_tag',
                'category',
                'status',
                'location',
                'purchased_at',
                'warranty_expires_at',
                'metadata',
            ]);
            $table->string('serial_number')->nullable()->change();
        });
    }
};
