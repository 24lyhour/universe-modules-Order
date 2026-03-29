<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Order\Database\Factories\RefundFactory;

class Refund extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
            'uuid',
            'order_id',
            'customer_id',
            'outlet_id',
            'refund_number',
            'wallet_id',
            'status',
            'total_amount',

    ];

    /**
     * protection the filed table
     */
    protected   $casts =
    [
        'total_amount'  => 'decimal:2',
        'status'        => OrderStatusEnum::class,
    ];
   
    /**
     * data testing 
     */
    protected static function newFactory(): RefundFactory
    {
        return RefundFactory::new();
    }

    /**
     * relation to the customer
     */
    public function Customer() :BelongsTo
    {
        return $this->BelongsTo(Customer::class, 'customer_id');
    }

    /**
     * relation to the outlet
     */
    public function Outlet() : BelongsTo
    {
        return $this->BelongsTo(Outlet::class , 'outlet_id');
    }

    /**
     * relation to the order
     */
    public function Order() : BelongsTo
    {
        return $this->BelongsTo(Order::class , 'order_id');
    }

    /**
     * relation to the order
     */
    public function Wallet() : BelongsTo
    {
        return $this->BelongsTo(Wallet::class , 'order_id');
    }


}
