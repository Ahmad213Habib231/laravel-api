

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
       
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password')->nullable();  // بيكون null لو سجل بجوجل أو فيسبوك
            $table->string('provider')->nullable();  // google أو facebook
            $table->string('provider_id')->nullable(); // ID من جوجل أو فيسبوك
            $table->string('otp')->nullable();
            $table->timestamps();
        });
        
    }

    public function down(): void {
        Schema::dropIfExists('users');
    }
};
