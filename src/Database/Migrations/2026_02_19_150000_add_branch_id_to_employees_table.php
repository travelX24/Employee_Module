<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('saas_company_id');
                $table->index('branch_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'branch_id')) {
                $table->dropColumn('branch_id');
            }
        });
    }
};

