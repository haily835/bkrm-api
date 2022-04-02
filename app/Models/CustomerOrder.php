<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

}