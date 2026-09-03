<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employee is DELIBERATELY separate from User — "tidak semua
     * pegawai punya akun atau bisa pakai HP" (the person's own words).
     * User (login access) becomes OPTIONAL and linked TO an Employee,
     * not the other way around. This is also the right foundation for
     * Payroll (Fase H) later: salary belongs to a PERSON who works
     * here, whether or not they can log in.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Nullable + unique: an Employee MAY be linked to a login
            // account, but never two Employees to the same account.
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('position')->nullable(); // free text, e.g. "Petugas Lapangan"
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
