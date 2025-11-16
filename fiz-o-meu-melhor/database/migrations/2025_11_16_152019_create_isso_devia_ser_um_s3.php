<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('issodeviaserums3', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_historic_id')
                ->constrained('upload_historics')
                ->cascadeOnDelete();
            $table->longText('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issodeviaserums3');
    }
};
