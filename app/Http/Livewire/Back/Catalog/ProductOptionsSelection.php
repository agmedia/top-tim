<?php

namespace App\Http\Livewire\Back\Catalog;

use App\Helpers\Country;
use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Options\Options;
use App\Models\Back\Catalog\Product\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 *
 */
class ProductOptionsSelection extends Component
{

    /**
     * @var array
     */
    public $values = [];

    /**
     * @var array
     */
    public $items = [];

    /**
     * @var array
     */
    public $item = [];

    /**
     * @var array
     */
    public $select_options = [];

    /**
     * @var int
     */
    public $select_first_option = 0;

    /**
     * @var null
     */
    public $first_option = null;

    /**
     * @var int
     */
    public $select_second_option = 0;

    /**
     * @var null
     */
    public $second_option = null;

    /**
     * @var string
     */
    public $step = 'start';

    /**
     * @var int
     */
    public $type = 0;

    /**
     * @var null | Product
     */
    public $product = null;


    /**
     * @return void
     */
    public function mount()
    {
        $this->setDefaultOptions();

        if ($this->product && $this->product->options()->count()) {
            $this->setPredefinedIOptions();
        }
    }


    /**
     * @param string $key
     * @param array  $item
     *
     * @return void
     */
    public function addItem(string $key, array $item = [])
    {
        if (empty($item)) {
            $item = $this->getEmptyItem();
        }

        array_push($this->items[$key]['options'], $item);
    }


    /**
     * @param string $key
     * @param string $sub_key
     * @param array  $item
     *
     * @return void
     */
    public function addSubItem(string $key, string $sub_key, array $item = [])
    {
        if (empty($item)) {
            $item = $this->getEmptyItem();
        }

        array_push($this->items[$key]['options'][$sub_key]['sub_options'], $item);
    }


    /**
     * @param string   $key
     * @param int      $opt_key
     * @param int|null $subopt_key
     *
     * @return void
     */
    public function deleteItem(string $key, int $opt_key, int $subopt_key = null)
    {
        if ($subopt_key !== null) {
            unset($this->items[$key]['options'][$opt_key]['sub_options'][$subopt_key]);

            return;
        }

        unset($this->items[$key]['options'][$opt_key]);
    }


    /**
     * @param int $type
     *
     * @return void
     */
    public function selectType(int $type)
    {
        $this->type = $type;

        if ($this->step == 'start') {
            $this->step = 'select';
        }
    }


    /**
     * @param $value
     *
     * @return void
     */
    public function updatedSelectFirstOption($value)
    {
        $this->first_option = intval($value);

        if ($this->type == 1) {
            $this->setDefaultOptions();
            $this->step = 'one';
        }
    }


    /**
     * @param $value
     *
     * @return void
     */
    public function updatedSelectSecondOption($value)
    {
        $this->second_option = intval($value);
        if ($this->type == 2) {
            $this->setDefaultOptions();
            $this->step = 'two';
        }
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.back.catalog.product-options-selection');
    }


    /**
     * @return array
     */
    private function getEmptyItem(): array
    {
        return [
            'id' => 0,
            'value' => '',
            'sku' => isset($this->product->sku) ? $this->product->sku : '',
            'quantity' => 0,
            'price' => 0,
            'sub_options' => []
        ];
    }


    /**
     * @return void
     */
    private function setPredefinedIOptions()
    {
        $values = $this->product->options()->get();

        if ($values->count()) {
            //
            if ($values->first()->parent == 'single') {
                $key = $values->first()->title->type;

                if ( ! isset($this->items[$key]['options'])) {
                    $this->items[$key]['options'] = [];
                }

                if ( ! isset($this->items[$key]['selections'])) {
                    $this->items[$key]['selections'] = [];
                }

                $this->first_option = $values->first()->option_id;
                $this->type = 1;
                $this->setDefaultOptions();
                $this->step = 'one';

                //
                foreach ($values as $value) {
                    $item = [
                        'id' => $value->id,
                        'value' => $value->option_id,
                        'sku' => $value->sku,
                        'qty' => $value->quantity,
                        'price' => $value->price,
                        'sub_options' => []
                    ];

                    $this->addItem($key, $item);
                }
            }

            //
            if ($values->first()->parent == 'option') {
                $key = $values->first()->top->first()->type;

                if ( ! isset($this->items[$key]['options'])) {
                    $this->items[$key]['options'] = [];
                }

                if ( ! isset($this->items[$key]['selections'])) {
                    $this->items[$key]['selections'] = [];
                    $this->items[$key]['sub_selections'] = [];
                }

                $this->first_option = $values->first()->parent_id;
                $this->second_option = $values->first()->option_id;
                $this->type = 2;
                $this->setDefaultOptions();
                $this->step = 'two';

                //
                foreach ($values->groupBy('parent_id') as $top_id => $value) {
                    $sub_options = [];

                    foreach ($value as $sub_value) {
                        $sub_item = [
                            'id' => 0,
                            'value' => $sub_value->option_id,
                            'sku' => $sub_value->sku,
                            'qty' => $sub_value->quantity,
                            'price' => $sub_value->price,
                            'sub_options' => []
                        ];

                        array_push($sub_options, $sub_item);
                    }

                    $item = [
                        'id' => 0,
                        'value' => $top_id,
                        'sku' => '',
                        'qty' => 0,
                        'price' => 0,
                        'sub_options' => $sub_options
                    ];

                    array_push($this->items[$key]['options'], $item);
                }
            }
        }
    }


    /**
     * @return void
     */
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
                        $this->items[$option->type]['sub_selections'][$sub_option->id] = [
                            'id' => $sub_option->id,
                            'title' => $sub_option->translation->title
                        ];
                    }
                }
            }
        }
        // end else
    }
}
