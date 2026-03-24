<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('legal_cases', function (Blueprint $table) {
        $table->json('attachments')->nullable()->after('initial_budget'); // ملفات طالب الخدمة
    });

    Schema::table('offers', function (Blueprint $table) {
        $table->json('attachments')->nullable()->after('proposal_text'); // ملفات المحامي في العرض
    });
}
};
