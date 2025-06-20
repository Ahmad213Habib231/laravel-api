<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('sessions', function (Blueprint $table) {
        $table->string('id')->primary(); // session_id as primary key
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
        $table->text('payload');
        $table->integer('last_activity');
        $table->timestamps();
    });
}

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
