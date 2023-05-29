<?php

namespace App\Models;

use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $fillable = [
        'title', 'description'
    ];


    // public function variants(){
    //     return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    // }

    public function productVariants(){
        return $this->hasMany(ProductVariant::class, 'variant_id', 'id');
    }
}
