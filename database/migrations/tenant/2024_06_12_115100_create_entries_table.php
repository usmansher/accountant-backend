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
        Schema::create('entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tag_id')->nullable();
            $table->uuid('entrytype_id');
            $table->bigInteger('number')->nullable();
            $table->date('date');
            $table->decimal('dr_total', 25, 2)->default(0.00);
            $table->decimal('cr_total', 25, 2)->default(0.00);
            $table->string('narration', 500)->nullable();
            $table->timestamps();

            $table->foreign('entrytype_id')->references('id')->on('entrytypes');
            $table->foreign('tag_id')->references('id')->on('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};
