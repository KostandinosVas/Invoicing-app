<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            $table->uuid('idempotency_key')->unique();
            $table->string('status', 20);
            $table->unsignedSmallInteger('attempt');

            $table->text('request_payload');
            $table->text('response_payload')->nullable();

            $table->string('mydata_mark')->nullable();
            $table->string('mydata_uid')->nullable();
            $table->string('mydata_authentication_code')->nullable();

            $table->json('errors')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
