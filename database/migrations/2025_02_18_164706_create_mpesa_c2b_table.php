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
        Schema::create('mpesa_c2b', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->nullable();
            $table->string('transaction_ID')->nullable();
            $table->string('transaction_Time')->nullable();
            $table->string('amount')->nullable();
            $table->string('business_shortcode')->nullable();
            $table->string('account_number')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('organization_account_balance')->nullable();
            $table->string('third_party_transaction_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('firstname')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_c2b');
    }
};
