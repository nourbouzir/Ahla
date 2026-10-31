<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gateway_ref')->nullable()->unique();
            $table->decimal('amount', 10, 3);
            $table->decimal('refunded_amount', 10, 3)->default(0);
            $table->char('currency', 3)->default('TND');
            $table->string('status', 20)->index();
            $table->timestamp('authorised_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
