<?php

namespace App\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Product\Entities\Product;

class SearchProduct extends Component
{

    public $query;
    public $search_results;
    public $how_many;

    public function mount()
    {
        $this->query = '';
        $this->how_many = 5;
        $this->search_results = Collection::empty();
    }

    public function render()
    {
        return view('livewire.search-product');
    }

    public function updatedQuery()
    {
        $this->search_results = Product::where('product_name', 'like', '%' . $this->query . '%')
            ->orWhere('product_code', 'like', '%' . $this->query . '%')
            ->take($this->how_many)->get();
    }

    public function loadMore()
    {
        $this->how_many += 5;
        $this->updatedQuery();
    }

    public function resetQuery()
    {
        $this->query = '';
        $this->how_many = 5;
        $this->search_results = Collection::empty();
    }

    public function findProductByBarcode($barcode)
    {
        // Find the product by barcode (product_code)
        $product = Product::where('product_code', $barcode)->first();
        return $product;
    }


    public function selectProduct($scannedCode)
    {
        // Query the Product model where product_code matches scannedCode
        $productModel = Product::where('product_code', $scannedCode)->first();

        if ($productModel) {
            // Create an array with all the required key-value pairs
            $product = [
                'id' => $productModel->id,
                'category_id' => $productModel->category_id,
                'product_name' => $productModel->product_name,
                'product_code' => $productModel->product_code,
                'product_barcode_symbology' => $productModel->product_barcode_symbology,
                'product_quantity' => $productModel->product_quantity,
                'product_cost' => $productModel->product_cost,
                'product_price' => $productModel->product_price,
                'product_unit' => $productModel->product_unit,
                'product_stock_alert' => $productModel->product_stock_alert,
                'product_order_tax' => $productModel->product_order_tax,
                'product_tax_type' => $productModel->product_tax_type,
                'product_note' => $productModel->product_note,
                'created_at' => $productModel->created_at,
                'updated_at' => $productModel->updated_at
            ];

            // Dispatch the product array with the specified structure
            $this->dispatch('productSelected', $product);
        } else {
            $this->dispatch('productNotFound', $scannedCode);
        }
    }

}
