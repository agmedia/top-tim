<?php

namespace App\Http\Livewire\Back\Marketing;

use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\Product\Product;
use App\Models\Back\Catalog\Publisher;
use App\Models\Back\Marketing\Blog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ActionGroupList extends Component
{
    use WithPagination;

    /**
     * @var string[]
     */
    protected $listeners = [
        'groupUpdated' => 'groupSelected'
    ];

    /**
     * @var string
     */
    public $search = '';

    /**
     * @var array
     */
    public $search_results = [];

    /**
     * @var bool
     */
    public $is_search_active = true;

    /**
     * @var string
     */
    public $group = '';

    /**
     * @var Collection
     */
    public $list = [];


    /**
     * @return void
     */
    public function mount()
    {
        if ( ! empty($this->list)) {
            $ids = $this->list;
            $this->list = [];

            foreach ($ids as $id) {
                $this->addItem(intval($id));
            }

            $this->render();
        }
    }


    /**
     * @param string $value
     *
     * @return void
     */
    public function updatingSearch(string $value): void
    {
        $this->search = $value;
        $this->search_results = [];

        if ($this->search != '') {
            switch ($this->group) {
                case 'product':
                    $this->search_results = Product::query()->whereNotIn('id', array_keys($this->list))
                                                   ->whereHas('translation', function ($query) use ($value) {
                                                       $query->where('name', 'like', '%' . $value . '%')->orwhere('sku', 'like', '%' . $value . '%');
                                                   })->limit(10)->get();

                    break;

                case 'category':
                    $this->search_results = Category::query()->whereNotIn('id', array_keys($this->list))
                                                    ->whereHas('translation', function ($query) use ($value) {
                                                        $query->where('title', 'like', '%' . $value . '%');
                                                    })->limit(5)->get();
                    break;

                case 'brand':
                    $this->search_results = Brand::query()->whereNotIn('id', array_keys($this->list))
                                                 ->whereHas('translation', function ($query) use ($value) {
                                                     $query->where('lang', current_locale())->where('title', 'like', '%' . $value . '%');
                                                 })->limit(5)->get();

                    break;

                case 'blog':
                    $this->search_results = Blog::query()->whereNotIn('id', array_keys($this->list))
                                                ->whereHas('translation', function ($query) use ($value) {
                                                    $query->where('title', 'like', '%' . $value . '%');
                                                })->limit(5)->get();
                    break;
            }
        }
    }


    /**
     * @param int $id
     *
     * @return void
     */
    public function addItem(int $id): void
    {
        $this->search = '';
        $this->search_results = [];

        switch ($this->group) {
            case 'product':
                $this->list[$id] = Product::where('id', $id)->with('translation')->first()->toArray();
                break;

            case 'category':
                $this->list[$id] = Category::query()->where('id', $id)->with('translation')->first()->toArray();
                break;

            case 'brand':
                $this->list[$id] = Brand::where('id', $id)->with('translation')->first()->toArray();
                break;

            case 'blog':
                $this->list[$id] = Blog::where('id', $id)->with('translation')->first()->toArray();
                break;
        }
    }


    /**
     * @param int $id
     *
     * @return void
     */
    public function removeItem(int $id): void
    {
        if ($this->list[$id]) {
            unset($this->list[$id]);
        }
    }


    /**
     * @param string $group
     *
     * @return void
     */
    public function groupSelected(string $group): void
    {
        $this->group = $group;
        $this->checkGroup();
    }


    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        if ( ! empty($this->list)) {
            $this->emit('list_full');
        } else {
            $this->emit('list_empty');
        }

        $this->checkGroup();

        return view('livewire.back.marketing.action-group-list', [
            'list' => $this->list,
            'group' => $this->group
        ]);
    }


    /**
     * @return string
     */
    public function paginationView()
    {
        return 'vendor.pagination.bootstrap-livewire';
    }


    /**
     * @return void
     */
    private function checkGroup(): void
    {
        if (in_array($this->group, ['all', 'total'])) {
            $this->is_search_active = false;
        } else {
            $this->is_search_active = true;
        }
    }
}
