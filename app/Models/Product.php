<?php

namespace App\Models;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];


    public function productVariantPrices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'id');
    }

    public function productvariants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }

   
}
