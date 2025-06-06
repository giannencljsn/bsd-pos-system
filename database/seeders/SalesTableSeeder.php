<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;

class SalesTableSeeder extends Seeder
{
    public function run()
    {
        // Example dummy sales data
        Sale::create([
            'date' => now()->subDays(3),
            'reference' => 'PSL001',
            'customer_id' => 1,
            'customer_name' => 'John Doe',
            'tax_percentage' => 6,
            'tax_amount' => 600,
            'discount_percentage' => 10,
            'discount_amount' => 1000,
            'shipping_amount' => 500,
            'total_amount' => 10000,
            'paid_amount' => 9000,
            'due_amount' => 1000,
            'status' => 'Completed',
            'payment_status' => 'Partial',
            'payment_method' => 'Cash',
            'note' => 'First sale note',
        ]);

        Sale::create([
            'date' => now()->subDay(),
            'reference' => 'PSL002',
            'customer_id' => 2,
            'customer_name' => 'Jane Smith',
            'tax_percentage' => 6,
            'tax_amount' => 300,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 5000,
            'paid_amount' => 5000,
            'due_amount' => 0,
            'status' => 'Completed',
            'payment_status' => 'Paid',
            'payment_method' => 'Credit Card',
            'note' => null,
        ]);
    }
}
