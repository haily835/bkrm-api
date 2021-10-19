<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Store extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function branches() 
    {
        return this->hasMany(Branch::class);
    }
}
