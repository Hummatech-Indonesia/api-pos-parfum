<?php

namespace App\Exports;

use App\Contracts\Repositories\Transaction\TransactionRepository;
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

class TransactionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    private TransactionRepository $transactionRepository;
    private array $filters;

    public function __construct(TransactionRepository $transactionRepository, $filters = [])
    {
        $this->filters = $filters;
        $this->transactionRepository = $transactionRepository;
    }

    public function collection()
    {
        return $this->transactionRepository->getDataForExport($this->filters);
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
            $transaction->cashier_id ?? 'kasir',
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
