<?php

namespace App\Exports;

use App\Contracts\Repositories\Transaction\ShiftUserRepository;
use App\Models\ShiftUser;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ShiftUserExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
{
    protected array $filters;
    private ShiftUserRepository $shiftUserRepository;

    public function __construct(ShiftUserRepository $shiftUserRepository, $filters = [])
    {
        $this->shiftUserRepository = $shiftUserRepository;
        $this->filters = $filters;
    }

    public function collection()
    {
        return $this->shiftUserRepository->getDataForExport($this->filters);
    }

    public function headings(): array
    {
        return ['User', 'Waktu', 'Tanggal', 'Uang Keluar', 'Uang Masuk'];
    }
    

    public function map($shift): array
    {
        return [
            $shift->user?->name ?? null,
            $shift->date ? Carbon::parse($shift->date)->format('H:i:s') : null,
            $shift->date ? Carbon::parse($shift->date)->format('d-m-Y') : null,
            $shift->start_price,
            $shift->end_price,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
