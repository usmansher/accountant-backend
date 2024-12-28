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
        Schema::create('entry_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('entry_id');
            $table->uuid('ledger_id');
            $table->decimal('amount', 25, 2)->default(0.00);
            $table->string('narration')->nullable();
            $table->char('dc', 1);
            $table->date('reconciliation_date')->nullable();
            $table->timestamps();

            $table->foreign('entry_id')->references('id')->on('entries');
            $table->foreign('ledger_id')->references('id')->on('ledgers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entry_items');
    }
};
