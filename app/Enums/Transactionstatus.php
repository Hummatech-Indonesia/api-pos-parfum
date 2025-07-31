<?php

namespace App\Enums;

enum Transactionstatus: string
{
    case SUCCESS = 'Success';
    case COMPLETE = 'Complete';
    case CANCELLED = 'Cancelled';
}