<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_desk_categories', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
        });

        Schema::table('service_desk_categories', function (Blueprint $table) {
            if (Schema::hasIndex('service_desk_categories', ['department_id', 'slug'], 'unique')) {
                $table->dropUnique(['department_id', 'slug']);
            }

            $table->dropColumn('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_desk_categories', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('id')
                ->constrained('service_desk_departments')
                ->cascadeOnDelete();
        });
    }
};
