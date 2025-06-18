<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
     use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'CurrencyName', 'CurrencyCode', 'Country', 'image_url','image','image_path',
    ];

    public function userScans()
    {
        return $this->hasMany(UserScan::class);
    }
}
