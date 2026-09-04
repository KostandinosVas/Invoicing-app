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
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedSmallInteger('position');

            // Snapshot στοιχείων είδους
            $table->string('description');
            $table->string('unit', 20)->default('τεμ');
            $table->decimal('quantity', 12, 3);
            $table->bigInteger('unit_price_cents');
            $table->unsignedSmallInteger('vat_rate');

            // Υπολογισμένα σύνολα γραμμής
            $table->bigInteger('net_amount_cents');
            $table->bigInteger('vat_amount_cents');
            $table->bigInteger('total_cents');

            $table->timestamps();

            $table->unique(['invoice_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
