<?php

namespace App\Enums;

enum StockRequestStatus: string
{
    case PENDING = 'Pending';
    case APPROVED = 'Approved';
    case REJECTED = 'Rejected';
}