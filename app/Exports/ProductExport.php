<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class ProductExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings()
    {
        return ['ID', 'Name', 'Detail Sum Stock', 'Category', 'Created By', 'Description', 'Sum Purchase', 'Density', 'Unit Code', 'Price', 'Variant Name', 'Product Code', 'Transaction Detail Count', 'Stock'];
    }
}
