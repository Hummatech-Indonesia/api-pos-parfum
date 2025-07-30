<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case SUCCESS = 'Success';
    case COMPLETE = 'Complete';
    case CANCELLED = 'Cancelled';
}