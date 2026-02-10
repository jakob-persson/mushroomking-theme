<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adventure_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('adventure_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('path');          // storage path: adventures/xxx.jpg
            $table->unsignedInteger('sort')->default(0); // 0 = första bilden

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adventure_photos');
    }
};
