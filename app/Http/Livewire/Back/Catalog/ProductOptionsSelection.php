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

    public $select_options = [];

    public $select_first_option = 0;
    public $first_option = null;
    public $select_second_option = 0;
    public $second_option = null;

    public $step = 'start';

    public $type = 0;

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

        array_push($this->items[$key]['options'], $item);
    }


    public function addSubItem(string $key, string $sub_key, array $item = [])
    {
        if (empty($item)) {
            $item = $this->getEmptyItem();
        }

        array_push($this->items[$key]['options'][$sub_key]['sub_options'], $item);
    }


    public function deleteItem(int $key)
    {
        unset($this->items[$key]);
    }


    public function selectType(int $type)
    {
        $this->type = $type;

        if ($this->step == 'start') {
            $this->step = 'select';
        }
    }


    public function updatedSelectFirstOption($value)
    {
        $this->first_option = intval($value);

        if ($this->type == 1) {
            $this->setDefaultOptions();
            $this->step = 'one';
        }
    }


    public function updatedSelectSecondOption($value)
    {
        $this->second_option = intval($value);
        //dd($this->first_option, $this->second_option);
        if ($this->type == 2) {
            $this->setDefaultOptions();
            $this->step = 'two';
        }
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
            'price' => 0,
            'sub_options' => []
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
        $options = Options::query();

        if ( ! $this->type) {
            foreach ($options->get() as $option) {
                $this->select_options[$option->type] = [
                    'id' => $option->id,
                    'title' => $option->group
                ];
            }

        } else {
            $default = Options::query()->where('id', $this->first_option)->first();

            if ($default) {
                foreach ($options->where('group', Str::slug($default->group))->get() as $option) {
                    $this->items[$option->type]['options'] = [];
                    $this->items[$option->type]['selections'][$option->id] = [
                        'id' => $option->id,
                        'title' => $option->translation->title
                    ];
                }

                if ($this->type == 2 && $this->second_option) {
                    $sub_options = Options::query();
                    $sub_default = Options::query()->where('id', $this->second_option)->first();

                    foreach ($sub_options->where('group', Str::slug($sub_default->group))->get() as $sub_option) {
                        $this->items[$option->type]['sub_options'] = [];
                        $this->items[$option->type]['sub_selections'][$sub_option->id] = [
                            'id' => $sub_option->id,
                            'title' => $sub_option->translation->title
                        ];
                    }
                }
            }
        }


        /*foreach ($options as $option) {
            $this->items[$option->type]['options'] = [];
            $this->items[$option->type]['selections'][] = [
                'id' => $option->id,
                'title' => $option->translation->title
            ];

            $this->select_options[$option->type] = [
                'id' => $option->id,
                'title' => $option->group
            ];
        }*/

    }
}
