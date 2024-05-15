<?php

namespace App\Http\Livewire\Back\Catalog;

use App\Helpers\Country;
use App\Models\Back\Catalog\Attributes\Attributes;
use Illuminate\Support\Str;
use Livewire\Component;

class OptionsAddition extends Component
{

    public $values = [];

    public $items = [];

    public $item = [];


    public function mount()
    {
        if ( ! empty($this->values)) {
            $this->sortPredefinedItems();
        }

        //dd($this->items);
    }


    public function addItem(array $item = [])
    {
        if (empty($item)) {
            $item = $this->getEmptyItem();
        }

        array_push($this->items, $item);
    }


    public function deleteItem(int $key)
    {
        unset($this->items[$key]);
    }

    public function render()
    {
        return view('livewire.back.catalog.options-addition');
    }


    private function getEmptyItem(): array
    {
        $titles = [];

        foreach (ag_lang() as $lang) {
            $titles[$lang->code] = '';
        }

        return [
            'id' => 0,
            'title' => $titles,
            'sort_order' => 0
        ];
    }


    private function sortPredefinedItems()
    {
        $values = Attributes::query()->where('group', Str::slug($this->values->group))->get();

        foreach ($values as $value) {
            $titles = [];

            foreach (ag_lang() as $lang) {
                $titles[$lang->code] = $value->translation($lang->code)->title;
            }

            array_push($this->items, [
                'id' => $value->id,
                'title' => $titles,
                'sort_order' => $value->sort_order
            ]);
        }
    }
}
