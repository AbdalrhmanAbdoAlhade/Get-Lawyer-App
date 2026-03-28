<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('case_updates', function (Blueprint $table) {
            $table->id();
            // ربط التحديث بالقضية المعنية
            $table->foreignId('case_id')->constrained('legal_cases')->onDelete('cascade');

            // مين اللي كتب التحديث؟ (المحامي ولا العميل؟)
            $table->foreignId('user_id')->constrained('users');

            // نص التحديث أو الرسالة
            $table->text('message');

            // لو فيه مرفقات (صور مستندات، ملفات PDF) صلحناها في القضية
            $table->string('attachment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_updates');
    }
};
