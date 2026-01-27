<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // الشركة (Multi-tenant)
            $table->unsignedBigInteger('saas_company_id')->index();

            // رقم الموظف
            $table->string('employee_no', 50);

            // A) الأساسية
            $table->string('name_ar');
            $table->string('name_en')->nullable();

            $table->string('national_id', 50);
            $table->string('nationality', 100);
            $table->date('birth_date');
            $table->string('gender', 20);
            $table->string('marital_status', 50);
            $table->string('birth_place')->nullable();
            $table->unsignedSmallInteger('children_count')->nullable();

            // B) الوظيفة
            $table->string('sector', 150);
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('sub_department_id')->nullable()->constrained('departments')->nullOnDelete(); // اختياري
            $table->foreignId('job_title_id')->constrained('job_titles')->restrictOnDelete();

            $table->string('grade', 50);
            $table->string('job_function', 150); // الوظيفة (من القوائم)
            $table->unsignedBigInteger('manager_id')->nullable(); // self reference later
            $table->date('hired_at');
            $table->date('procedures_start_at')->nullable();

            // status + ended_at
            $table->string('status', 30)->default('ACTIVE')->index();
            $table->date('ended_at')->nullable();

            // D) المالية
            $table->string('contract_type', 50);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->unsignedSmallInteger('annual_leave_days')->default(0);
            $table->unsignedSmallInteger('contract_duration_months')->default(0);

            // E) الشخصية
            $table->string('mobile', 30);
            $table->string('mobile_alt', 30)->nullable();

            $table->string('email_work')->nullable();
            $table->string('email_personal')->nullable();

            $table->string('emergency_contact_name');
            $table->string('emergency_contact_phone', 30);
            $table->string('emergency_contact_phone_alt', 30)->nullable();
            $table->string('emergency_contact_relation', 100);

            $table->string('city', 150);
            $table->string('district', 150)->nullable();
            $table->text('address')->nullable();

            $table->string('bank_account_owner')->nullable();

            // F) المستندات (مسارات فقط الآن)
            $table->string('personal_photo_path')->nullable();
            $table->string('id_photo_path')->nullable();

            // تأكيد صحة المستندات
            $table->boolean('documents_verified')->default(false);
            $table->timestamp('documents_verified_at')->nullable();
            $table->unsignedBigInteger('documents_verified_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Unique per company
            $table->unique(['saas_company_id', 'employee_no']);
            $table->unique(['saas_company_id', 'national_id']);
        });

        // علاقات إضافية بعد إنشاء الجدول (أكثر أماناً)
        Schema::table('employees', function (Blueprint $table) {

            // manager_id -> employees.id
            $table->foreign('manager_id')
                ->references('id')->on('employees')
                ->nullOnDelete();

            // documents_verified_by -> users.id (اختياري)
            if (Schema::hasTable('users')) {
                $table->foreign('documents_verified_by')
                    ->references('id')->on('users')
                    ->nullOnDelete();
            }

            // ربط saas_company_id لو عندك جدول شركات باسم مختلف
            if (Schema::hasTable('saas_companies')) {
                $table->foreign('saas_company_id')
                    ->references('id')->on('saas_companies')
                    ->cascadeOnDelete();
            } elseif (Schema::hasTable('companies')) {
                $table->foreign('saas_company_id')
                    ->references('id')->on('companies')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};




