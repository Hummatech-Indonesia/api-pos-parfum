<?php 

namespace App\Contracts\Interfaces;

interface ProfitLossInterface
{
    public function getOutletProfitLoss(string $outletId, ?int $month = null, ?int $year = null): array;

}