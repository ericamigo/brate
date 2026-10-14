<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_rate_bills', function (Blueprint $table) {
            $table->string('case_number')->nullable()->after('unit_id');
            $table->string('stage')->nullable()->default('unverified')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('business_rate_bills', function (Blueprint $table) {
            $table->dropColumn(['case_number', 'stage']);
        });
    }
};
