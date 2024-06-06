<?php

namespace App\Http\Livewire\Back\Layout\Search;

use App\Helpers\Helper;
use App\Models\Back\Settings\SizeGuide;
use App\Models\Back\Settings\SizeGuideTranslation;
use Carbon\Carbon;
use Livewire\Component;

class SizeguideSearch extends Component
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
    public $sizeguide_id = 0;

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
        if ($this->sizeguide_id) {
            $sizeguide = SizeGuide::find($this->sizeguide_id);

            if ($sizeguide) {
                $this->search = $sizeguide->translation->title;
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

            $this->search_results = (new SizeGuide())->newQuery()
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
    public function addSizeGuide($id)
    {
        $sizeguide = (new SizeGuide())->where('id', $id)->first();

        $this->search_results = [];
        $this->search         = $sizeguide->translation->title;
        $this->sizeguide_id       = $sizeguide->id;

        if ($this->list) {
            return $this->emit('sizeguideSelect', ['sizeguide' => $sizeguide->toArray()]);
        }
    }




    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        if ($this->search == '') {
            $this->sizeguide_id = 0;

        }

        return view('livewire.back.layout.search.sizeguide-search');
    }
}
