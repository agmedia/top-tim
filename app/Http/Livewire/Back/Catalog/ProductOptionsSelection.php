<?php

namespace App\Http\Livewire\Back\Catalog;

use App\Helpers\Country;
use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class ProductOptionsSelection extends Component
{

    public $values = [];

    public $items = [];

    public $item = [];

    public $type = 'text';

    /**
     * @var null | Product
     */
    public $product = null;


    public function mount()
    {
        //dd($this->product);

        $this->setDefaultOptions();

        if ($this->product) {
            $this->setPredefinedIOptions();
        }

        //dd($this->items);
    }


    public function addItem(string $key, array $item = [])
    {
        if (empty($item)) {
            $item = $this->getEmptyItem();
        }

        //dd($key);

        //Log::info($item);

        array_push($this->items[$key]['options'], $item);
    }


    public function deleteItem(int $key)
    {
        unset($this->items[$key]);
    }

    public function render()
    {
        return view('livewire.back.catalog.product-options-selection');
    }


    private function getEmptyItem(): array
    {
        return [
            'id' => 0,
            'value' => '',
            'sku' => $this->product->sku,
            'quantity' => 0,
            'price' => 0
        ];
    }


    private function setPredefinedIOptions()
    {
        /*$values = $this->product->options()->get();

        foreach ($values as $value) {
            $titles = [];

            foreach (ag_lang() as $lang) {
                $titles[$lang->code] = $value->translation($lang->code)->title;
            }

            array_push($this->items, [
                'id' => $value->id,
                'value' => $value->value,
                'sku' => $value->sku,
                'quantity' => $value->quantity,
                'price' => $value->price
            ]);
        }*/
    }


    private function setDefaultOptions()
    {
        $options = Options::query()->get();

        foreach ($options as $option) {
            $this->items[$option->type]['options'] = [];
            $this->items[$option->type]['selections'][] = [
                'id' => $option->id,
                'title' => $option->translation->title
            ];
        }

    }
}
