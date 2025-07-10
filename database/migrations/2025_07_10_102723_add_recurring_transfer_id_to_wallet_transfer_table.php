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
        Schema::table('wallet_transfers', function (Blueprint $table) {
            $table->foreignId('recurring_transfer_id')
                ->nullable()
                ->constrained('recurring_transfers')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transfers', function (Blueprint $table) {
            $table->dropForeign(['recurring_transfer_id']);
            $table->dropColumn('recurring_transfer_id');
        });
    }
};
