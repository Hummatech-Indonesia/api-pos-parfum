<?php

namespace App\Exports;

use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    protected array $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Transaction::query()
            ->with(['store', 'user', 'transaction_details'])
            ->when(Auth::user()?->outlet_id ?? Auth::user()?->outlet?->id, function ($q, $outletId) {
                $q->where('outlet_id', $outletId);
            });

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('payment_time', '>=', Carbon::createFromFormat('d-m-Y', $this->filters['start_date'])->format('Y-m-d'));
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('payment_time', '<=', Carbon::createFromFormat('d-m-Y', $this->filters['end_date'])->format('Y-m-d'));
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Nama Kasir',
            'Nama Pembeli',
            'Jumlah Yang Dibeli',
            'Total Harga',
            'Tanggal Pembelian',
        ];
    }

    public function map($transaction): array
    {
        return [
            Auth::user()?->name ?? null,
            $transaction->user_name,
            $transaction->transaction_details?->count() ?? 0,
            $transaction->amount_price,
            $transaction->payment_time ? Carbon::parse($transaction->payment_time)->format('d-m-Y') : null,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [ // Header row
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }
}
