<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('label');
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('email')->unique();
            $table->string('currency_symbol')->nullable();
            $table->string('currency_format')->nullable();
            $table->string('decimal_places')->nullable();
            $table->string('date_format')->nullable();

            $table->date('fy_start')->nullable();
            $table->date('fy_end')->nullable();

            $table->json('data')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
}
