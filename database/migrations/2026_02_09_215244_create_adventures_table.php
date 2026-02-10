<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('location')->nullable();
            $table->date('start_date')->nullable();
            $table->text('adventure_text')->nullable();

            // types = {"Chanterelles": 1.2, "Boletus": 0.4}
            $table->json('types')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventures');
    }
};
