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
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users'); // العميل صاحب الطلب

            $table->string('title');            // عنوان القضية
            $table->text('description');        // التفاصيل
            $table->string('category');         // المجال: جنائي، مالي، أحوال شخصية...
            $table->decimal('initial_budget', 10, 2); // السعر الذي وضعه العميل في البداية

            // تتبع حالة القضية كما طلبت
            $table->enum('status', ['pending', 'processing', 'completed', 'unresolved'])->default('pending');

            // المحامي أو المكتب الذي تم قبوله (يملأ عند تغيير الحالة لـ processing)
            $table->foreignId('accepted_provider_id')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
