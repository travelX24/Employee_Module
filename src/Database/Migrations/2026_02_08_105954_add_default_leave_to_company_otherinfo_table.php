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
        Schema::table('saas_company_otherinfo', function (Blueprint $table) {
            $table->smallInteger('default_annual_leave_days')->default(30)->after('datetime_format')->comment('الإجازة السنوية الافتراضية للموظفين الجدد');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saas_company_otherinfo', function (Blueprint $table) {
            $table->dropColumn('default_annual_leave_days');
        });
    }
};
