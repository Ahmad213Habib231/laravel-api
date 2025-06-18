<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('CurrencyName')->nullable()->change();
            $table->string('CurrencyCode')->nullable()->change();
            $table->string('Country')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('CurrencyName')->nullable(false)->change();
            $table->string('CurrencyCode')->nullable(false)->change();
            $table->string('Country')->nullable(false)->change();
        });
    }
};
