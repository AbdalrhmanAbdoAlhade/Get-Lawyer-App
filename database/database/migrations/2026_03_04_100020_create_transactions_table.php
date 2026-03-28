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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases');
            $table->foreignId('user_id')->constrained('users'); // من دفع المال
            $table->decimal('amount', 10, 2);
            $table->string('payment_gateway_id')->nullable(); // رقم العملية من بوابة الدفع
            $table->enum('type', ['deposit', 'payout', 'refund']); // إيداع، تحويل للمحامي، استرداد
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
