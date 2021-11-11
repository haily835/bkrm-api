<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $hidden = [
        'id',
    ];

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    protected $casts = [
        'creation_date' => 'datetime:Y-m-d',
        'approved_date' => 'datetime:Y-m-d',
        'payment_date' => 'datetime:Y-m-d',
    ];
}
