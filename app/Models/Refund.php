<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    protected $hidden = [
        'id',
    ];

    function refundDetails() {
        return $this->hasMany(RefundDetail::class);
    }
}
