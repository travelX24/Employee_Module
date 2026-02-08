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
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('daily_wage', 10, 2)->nullable()->after('basic_salary')->comment('الأجر اليومي');
            $table->decimal('hourly_wage', 10, 2)->nullable()->after('daily_wage')->comment('الأجر بالساعة');
            $table->decimal('minute_wage', 10, 4)->nullable()->after('hourly_wage')->comment('الأجر بالدقيقة');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['daily_wage', 'hourly_wage', 'minute_wage']);
        });
    }
};
