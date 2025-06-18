<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // أولاً: تحديث كل السجلات اللي Country فيها NULL
        DB::table('images')
            ->whereNull('Country')
            ->update(['Country' => 'Egypt']);

        // ثانياً: تعديل العمود نفسه
        Schema::table('images', function (Blueprint $table) {
            $table->string('Country')->default('Egypt')->change();
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->string('Country')->nullable()->default(null)->change();
        });
    }
};
