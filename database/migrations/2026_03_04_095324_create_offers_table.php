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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('legal_cases')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users'); // المحامي أو المكتب مقدم العرض

            $table->decimal('offered_price', 10, 2); // السعر المقدم من المحامي
            $table->text('proposal_text');           // تفاصيل العرض (كيف سيحل القضية)

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
