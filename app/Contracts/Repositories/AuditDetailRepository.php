<?php

namespace App\Contracts\Repositories;

use App\Contracts\Interfaces\AuditDetailInterface;
use App\Models\AuditDetail;

class AuditDetailRepository implements AuditDetailInterface
{
    public function store(array $data): mixed
    {
        return AuditDetail::create($data);
    }

    public function update(mixed $id, array $data): mixed
    {
        $auditDetail = AuditDetail::find($id);

        if (!$auditDetail) {
            return null;
        }

        $auditDetail->update($data);
        return $auditDetail;
    }

    public function findByAuditAndProductDetail($auditId, $productDetailId): ?AuditDetail
    {
        return AuditDetail::where('audit_id', $auditId)
            ->where('product_detail_id', $productDetailId)
            ->first();
    }
}
