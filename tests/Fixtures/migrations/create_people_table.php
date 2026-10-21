<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->index();
            $table->string('email')->nullable();
            $table->integer('age')->nullable();
            $table->boolean('active')->default(true);
            $table->date('birth_date')->nullable();
            $table->timestamps();
        });
    }
};
