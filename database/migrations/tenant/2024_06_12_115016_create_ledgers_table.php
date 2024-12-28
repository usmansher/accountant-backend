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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->decimal('op_balance', 25, 2)->default(0.00);
            $table->char('op_balance_dc', 1);
            $table->integer('type')->default(0);
            $table->boolean('reconciliation')->default(0);
            $table->string('notes', 500);
            $table->timestamps();
            $table->foreign('group_id')->references('id')->on('groups');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
