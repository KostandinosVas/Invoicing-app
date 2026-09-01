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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('series_id')->constrained('series')->restrictOnDelete();

            $table->string('document_type', 30);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('number')->nullable();
            $table->date('issue_date');

            // Snapshot στοιχείων πελάτη
            $table->string('customer_name');
            $table->string('customer_vat_number', 20)->nullable();
            $table->string('customer_tax_office')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_postal_code', 10)->nullable();
            $table->string('customer_country', 2)->default('GR');

            // Σύνολα σε λεπτά
            $table->bigInteger('net_amount_cents')->default(0);
            $table->bigInteger('vat_amount_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);

            // Αναφορά σε αρχικό παραστατικό (πιστωτικά / ακυρωτικά)
            $table->foreignId('related_invoice_id')->nullable()
                ->constrained('invoices')->restrictOnDelete();

            // myDATA
            $table->string('mydata_mark')->nullable()->unique();
            $table->timestamp('mydata_submitted_at')->nullable();

            $table->timestamps();

            $table->unique(['series_id', 'number']);
            $table->index(['company_id', 'issue_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
