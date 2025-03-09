<?php

namespace App\Http\Livewire\Back\Catalog;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Attributes\Attributes;
use App\Models\Back\Catalog\Attributes\AttributesTranslation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class AttributeSearchNewInput extends Component
{

    /**
     * @var string
     */
    public $search = '';

    /**
     * @var array
     */
    public $search_results = [];

    /**
     * @var string
     */
    public $group = '';

    /**
     * @var int
     */
    public $attribute_id = 0;

    /**
     * @var bool
     */
    public $show_add_window = false;

    /**
     * @var null|bool
     */
    public $list = null;

    /**
     * @var array
     */
    public $new = [
        'title' => ''
    ];


    /**
     *
     */
    public function mount()
    {
        if ($this->attribute_id) {
            $attribute = Attributes::find($this->attribute_id);

            if ($attribute) {
                $this->search = $attribute->translation->title;
            }
        }
    }


    /**
     *
     */
    public function viewAddWindow()
    {
        $this->show_add_window = ! $this->show_add_window;
    }


    /**
     *
     */
    public function updatingSearch($value)
    {
        $this->search         = $value;
        $this->search_results = [];

        if ($this->search != '') {
            $search = $this->search;

            $this->search_results = (new Attributes())->newQuery()
                                                      ->where('group', $this->group)
                                                      ->whereHas('translation', function ($query) use ($search) {
                                                          $query->where('title', 'LIKE', '%' . $search . '%');
                                                      })
                                                      ->limit(5)
                                                      ->get();
        }
    }


    /**
     * @param $id
     */
    public function addAttribute($id)
    {
        $attribute = (new Attributes())->where('id', $id)->first();

        $this->search_results = [];
        $this->search         = $attribute->translation->title;
        $this->attribute_id   = $attribute->id;

        if ($this->list) {
            return $this->emit('attributeSelect', ['attribute' => $attribute->toArray()]);
        }
    }


    /**
     *
     */
    public function makeNewAttribute()
    {
        if ($this->new['title'] == '') {
            return $this->emit('error_alert', ['message' => 'Molimo vas da popunite sve podatke!']);
        }

        $id = Attributes::insertGetId([
            'group'       => Str::slug($this->group),
            'type'        => 'text',
            'sort_order' => 0,
            'status'     => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        if ($id) {
            AttributesTranslation::createFast($id, $this->new['title'], $this->group);

            $this->show_add_window = false;

            $attribute          = Attributes::find($id);
            $this->attribute_id = $attribute->id;
            $this->search       = $attribute->translation->title;

            return $this->emit('success_alert', ['message' => 'Atribut je uspješno dodan..!']);
        }

        return $this->emit('error_alert');
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        if ($this->search == '') {
            $this->attribute_id = 0;

            if ($this->list) {
                $this->emit('attributeSelect', ['attribute' => ['id' => '']]);
            }
        }

        return view('livewire.back.catalog.attribute-search-new-input');
    }
}
