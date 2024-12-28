<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class EntryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        tenancy()->initialize('ots');
        $data = [
            [
                'id' => Str::uuid(),
                'label' => 'receipt',
                'name' => 'Receipt',
                'description' => 'Received in Bank account or Cash account',
                'base_type' => 1,
                'numbering' => 1,
                'prefix' => '',
                'suffix' => '',
                'zero_padding' => 0,
                'restriction_bankcash' => 2
            ],
            [
                'id' => Str::uuid(),
                'label' => 'payment',
                'name' => 'Payment',
                'description' => 'Payment made from Bank account or Cash account',
                'base_type' => 1,
                'numbering' => 1,
                'prefix' => '',
                'suffix' => '',
                'zero_padding' => 0,
                'restriction_bankcash' => 3
            ],
            [
                'id' => Str::uuid(),
                'label' => 'contra',
                'name' => 'Contra',
                'description' => 'Transfer between Bank account and Cash account',
                'base_type' => 1,
                'numbering' => 1,
                'prefix' => '',
                'suffix' => '',
                'zero_padding' => 0,
                'restriction_bankcash' => 4
            ],
            [
                'id' => Str::uuid(),
                'label' => 'journal',
                'name' => 'Journal',
                'description' => 'Transaction that does not involve a Bank account or Cash account',
                'base_type' => 1,
                'numbering' => 1,
                'prefix' => '',
                'suffix' => '',
                'zero_padding' => 0,
                'restriction_bankcash' => 5
            ]
        ];

        foreach ($data as $row) {
            DB::table('entrytypes')->insert($row);
        }
    }
}
