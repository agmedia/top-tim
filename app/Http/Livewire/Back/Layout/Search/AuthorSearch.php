<?php

namespace App\Http\Livewire\Back\Layout\Search;

use App\Helpers\Helper;
use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\BrandTranslation;
use Carbon\Carbon;
use Livewire\Component;

class AuthorSearch extends Component
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
     * @var int
     */
    public $brand_id = 0;

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
        if ($this->brand_id) {
            $brand = Brand::find($this->brand_id);

            if ($brand) {
                $this->search = $brand->translation->title;
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

            $this->search_results = (new Brand())->newQuery()
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
    public function addAuthor($id)
    {
        $brand = (new Brand())->where('id', $id)->first();

        $this->search_results = [];
        $this->search         = $brand->translation->title;
        $this->brand_id       = $brand->id;

        if ($this->list) {
            return $this->emit('brandSelect', ['brand' => $brand->toArray()]);
        }
    }


    /**
     *
     */
    public function makeNewAuthor()
    {
        if ($this->new['title'] == '') {
            return $this->emit('error_alert', ['message' => 'Molimo vas da popunite sve podatke!']);
        }

        $id = Brand::insertGetId([
            'letter'     => Helper::resolveFirstLetter($this->new['title']),
            'featured'   => 0,
            'sort_order' => 0,
            'status'     => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        if ($id) {
            BrandTranslation::createFast($id, $this->new['title']);

            $this->show_add_window = false;

            $brand          = Brand::find($id);
            $this->brand_id = $brand->id;
            $this->search   = $brand->translation->title;

            return $this->emit('success_alert', ['message' => 'Brand je uspješno dodan..!']);
        }

        return $this->emit('error_alert');
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        if ($this->search == '') {
            $this->brand_id = 0;

            if ($this->list) {
                $this->emit('brandSelect', ['brand' => ['id' => '']]);
            }
        }

        return view('livewire.back.layout.search.author-search');
    }
}
