<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_template', function (Blueprint $table) {

            $table->id();
            $table->string('judul');
            $table->text('isi');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_template');
    }
};
