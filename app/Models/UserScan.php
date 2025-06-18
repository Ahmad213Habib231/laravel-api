<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserScan extends Model
{

        use HasFactory;

        protected $fillable = [
            'user_id', 'currency_id', 'recognized_at', 'accuracy', 'image_url', 'result',
        ];


        public function user() {
            return $this->belongsTo(User::class);
        }
        // public function currencies() {
        //     return $this->belongsTo(Image::class);
        // }
        public function currency()
        {
            return $this->belongsTo(Currency::class);
        }
}
