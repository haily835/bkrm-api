<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $hidden = [
        'id',
    ];

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }


    protected $casts = [
        'paid_date' => 'datetime:Y-m-d',
    ];
}
