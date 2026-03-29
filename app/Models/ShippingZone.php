<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Order\Database\Factories\ShippingZoneFactory;

class ShippingZone extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [];

    // protected static function newFactory(): ShippingZoneFactory
    // {
    //     // return ShippingZoneFactory::new();
    // }
}
