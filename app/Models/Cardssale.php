<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cardssale extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_name',
        'quantity',
        'price',
        'user_users'
    ];
}
