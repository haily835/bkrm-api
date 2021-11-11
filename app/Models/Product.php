<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    protected $hidden = [
        'id',
        'store_id',
        'category_id'
    ];

    public function productPrices()
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function quantityAvailable()
    {
        $transactions = $this->hasMany(InventoryTransaction::class);
        $quantityAvailable = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->purchase_order_id | $transaction->refund_id) {
                $quantityAvailable += $transaction->quantity;
            } else {
                $quantityAvailable -= $transaction->quantity;
            }
        }

        return $quantityAvailable;
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
  