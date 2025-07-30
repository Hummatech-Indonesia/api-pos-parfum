<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::all();
        foreach ($stores as $store) {
            Setting::create([
                'id' => (string) Str::uuid(),
                'code' => 'SET_MIN_PRODUCT',
                'name' => 'Pengaturan minimal stock produk',
                'descriptions' => 'Pengaturan untuk melakukan set minimal stock produk',
                'value_text' => env('SET_MIN_PRODUCT', 10),
                'group' => 'text',
                'store_id' => $store->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
