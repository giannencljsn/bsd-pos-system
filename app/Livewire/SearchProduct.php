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

    // This method is triggered whenever the query is updated after typing finishes or a barcode is scanned
    public function updatedQuery()
    {
        // Fetch matching products from the DB
        $this->search_results = Product::query()
            ->where('product_name', 'like', '%' . $this->query . '%')
            ->orWhere('product_code', 'like', '%' . $this->query . '%')
            ->limit($this->how_many)
            ->get();

        // Auto-select the first result
        $this->searchAndSelect();
    }



    // Automatically select the first product once the query has finished updating
    public function searchAndSelect()
    {
        if ($this->search_results->isNotEmpty()) {
            $this->selectProduct($this->search_results->first()); // Automatically select the first product
        }
        $this->resetQuery();
    }

    // Method to handle selecting a product (either when clicked or automatically selected)
    public function selectProduct($product)
    {
        // Logic for adding the product to the cart or performing any other actions
        $this->dispatch('productSelected', $product); // Dispatch an event to select the product
        // Clear the input and results
        $this->resetQuery();
    }

    // Load more results when the "Load More" button is clicked
    public function loadMore()
    {
        $this->how_many += 5;
        $this->updatedQuery(); // Re-run the query with the updated results
    }

    // Reset the query and search results
    public function resetQuery()
    {
        $this->query = '';
        $this->how_many = 5;
        $this->search_results = Collection::empty();
    }
}
