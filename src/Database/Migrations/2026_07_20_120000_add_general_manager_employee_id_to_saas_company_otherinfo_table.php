<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_company_otherinfo')) {
            return;
        }

        if (! Schema::hasColumn('saas_company_otherinfo', 'general_manager_employee_id')) {
            Schema::table('saas_company_otherinfo', function (Blueprint $table) {
                $table->unsignedBigInteger('general_manager_employee_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('employees')) {
            try {
                Schema::table('saas_company_otherinfo', function (Blueprint $table) {
                    $table->foreign('general_manager_employee_id', 'sco_general_manager_employee_id_foreign')
                        ->references('id')
                        ->on('employees')
                        ->nullOnDelete();
                });
            } catch (Throwable $e) {
                // The foreign key may already exist on some deployments.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('saas_company_otherinfo') || ! Schema::hasColumn('saas_company_otherinfo', 'general_manager_employee_id')) {
            return;
        }

        try {
            Schema::table('saas_company_otherinfo', function (Blueprint $table) {
                $table->dropForeign('sco_general_manager_employee_id_foreign');
            });
        } catch (Throwable $e) {
            // Ignore missing foreign key.
        }

        Schema::table('saas_company_otherinfo', function (Blueprint $table) {
            $table->dropColumn('general_manager_employee_id');
        });
    }
};
