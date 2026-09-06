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
        Schema::create('cheque_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->onDelete('cascade');
            $table->date('cheque_date');
            $table->decimal('amount', 15, 2);
            $table->string('amount_in_words');
            $table->string('cheque_number');
            $table->string('payee_name');
            $table->string('payer_name');
            $table->string('account_number');
            $table->json('presentment_dates');
            $table->string('return_reason');
            $table->string('other_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheque_details');
    }
};
