<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_desk_service_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_request_id');
            $table->foreign('service_request_id', 'sr_approvals_sr_id_fk')
                ->references('id')
                ->on('service_desk_service_requests')
                ->onDelete('cascade');
            
            // Fixed: Added explicit short name for the morph index
            $table->morphs('approver', 'sr_approvals_approver_idx');
            
            $table->string('status', 16)->default('pending');
            $table->text('comment')->nullable();
            $table->unsignedInteger('step_order')->default(1);
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_desk_service_request_approvals');
    }
};
