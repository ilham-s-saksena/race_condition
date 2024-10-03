<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::create([
            'name' => 'Sample Product',
            'description' => 'This is a sample product description.',
            'price' => 100.00,  // Harga produk
            'stock' => 1,      // Jumlah stok produk
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
