<?php

namespace Modules\Order\Enums;

enum DeliveryPaymentEnum : string
{


    /**
     * status delivery
     */
    case Padding = 'Padding';   
    case Paid    = 'Paid';
    case Failed  = 'Failed';


    /**
     * label the 
     */
    public function label() 
    {
       return match ($this) {
            self::Pending => 'Pending',
            self::Paid    => 'Paid',
            self::Failed  => 'Failed',
        
        };
    }
    
}
