<?php

use App\Models\UploadHistoric;
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
        Schema::create('upload_historics', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hash', 64);
            $table->date('reference_date');
            $table->enum('status', UploadHistoric::STATUSES)->default(UploadHistoric::STATUS_WAITING);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_historics');
    }
};

