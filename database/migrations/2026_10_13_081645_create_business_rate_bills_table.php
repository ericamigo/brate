<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_rate_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount', 14, 2);
            $table->index(['unit_id', 'period_start', 'period_end']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_rate_bills');
    }
};
