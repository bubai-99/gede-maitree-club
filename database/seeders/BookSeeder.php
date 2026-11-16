<?php

namespace Database\Seeders;

use App\Enums\BookCode;
use App\Enums\FeePeriod;
use App\Models\Book;
use App\Models\FeePlan;
use Illuminate\Database\Seeder;

/**
 * The three account books and the fee plans. Fees are SAMPLE amounts from the prototype.
 */
class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [BookCode::Club, 'Club book', 'ক্লাব খাতা'],
            [BookCode::Catering, 'Catering book', 'ক্যাটারিং খাতা'],
            [BookCode::Nursery, 'Nursery book', 'নার্সারি খাতা'],
        ];

        foreach ($books as [$code, $name, $nameBn]) {
            Book::query()->updateOrCreate(['code' => $code], ['name' => $name, 'name_bn' => $nameBn]);
        }

        FeePlan::query()->updateOrCreate(['name' => 'Monthly'], [
            'name_bn' => 'মাসিক',
            'amount' => (int) config('club.monthly_fee'),
            'period' => FeePeriod::Monthly,
            'admission_fee' => (int) config('club.admission_fee'),
        ]);

        FeePlan::query()->updateOrCreate(['name' => 'Yearly'], [
            'name_bn' => 'বার্ষিক',
            'amount' => (int) config('club.monthly_fee') * 12,
            'period' => FeePeriod::Yearly,
            'admission_fee' => (int) config('club.admission_fee'),
        ]);
    }
}
