<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('income_classification', 30)->default('services_provision');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->string('income_classification', 30)->default('services_provision');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('mydata_invoice_type', 10)->nullable();
            $table->unsignedSmallInteger('payment_method')->default(3);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('income_classification');
        });

        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropColumn('income_classification');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['mydata_invoice_type', 'payment_method']);
        });
    }
};
