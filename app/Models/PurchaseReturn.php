<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;
    protected $guarded = [];


    function purchaseReturnDetails() {
        return $this->hasMany(PurchaseReturnDetail::class);
    }
}
