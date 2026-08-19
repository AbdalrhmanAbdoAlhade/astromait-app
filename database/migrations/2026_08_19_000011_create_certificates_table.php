<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            // Polymorphic: certifiable_type/certifiable_id -> Product or Service
            $table->morphs('certifiable');
            $table->foreignId('issued_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->string('qr_code_path')->nullable();
            $table->string('meteorite_type')->nullable();
            $table->string('origin_location')->nullable();
            $table->date('discovery_date')->nullable();
            $table->string('analysis_report_path')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
