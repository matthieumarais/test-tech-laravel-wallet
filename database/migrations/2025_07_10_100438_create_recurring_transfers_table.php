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
        Schema::create('recurring_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('recipient_email');
            $table->unsignedBigInteger('amount'); // Amount in cents
            $table->string('reason')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedBigInteger('frequency_days'); // Frequency in days
            $table->string('status')->default('active'); // Status of the recurring transfer
            $table->timestamp('next_execution_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_transfers');
    }
};
