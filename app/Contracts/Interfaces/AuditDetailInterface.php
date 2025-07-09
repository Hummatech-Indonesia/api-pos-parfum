<?php

namespace App\Contracts\Interfaces;

use App\Contracts\Interfaces\Eloquent\StoreInterface;
use App\Contracts\Interfaces\Eloquent\UpdateInterface;
use App\Models\AuditDetail;

interface AuditDetailInterface extends StoreInterface, UpdateInterface
{
    public function findByAuditAndProductDetail($auditId, $productDetailId): ?AuditDetail;
}
