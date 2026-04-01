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
        Schema::create('employee_leave_adjustments', function (Blueprint $row) {
            $row->id();
            $row->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $row->decimal('amount', 8, 2); // e.g., +1.00 or -1.00
            $row->text('reason');
            $row->string('file_path')->nullable();
            $row->string('file_name')->nullable();
            $row->foreignId('performer_id')->constrained('users')->onDelete('cascade');
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_adjustments');
    }
};
