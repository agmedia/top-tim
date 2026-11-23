<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\UserGroup;
use App\Models\CustomerDiscount;
use App\Models\Front\Catalog\Brand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DiscountController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless($request->user() && $request->user()->isActive()
                && $request->user()->roles()->whereIn('name', ['admin', 'superadmin'])->exists(), 403);
            return $next($request);
        });
    }

    public function index()
    {
        $sets = CustomerDiscount::selectRaw('MIN(id)')->groupBy('user_id', 'user_group_id', 'brand_id', 'applies_to_everyone');
        $discounts = CustomerDiscount::with(['user', 'group.translation', 'brand.translation'])
            ->whereIn('id', $sets)->orderByDesc('id')->paginate(30);
        if ($discounts->isNotEmpty()) {
            $levels = CustomerDiscount::where(function ($query) use ($discounts) {
                foreach ($discounts as $discount) {
                    $query->orWhere($this->target($discount));
                }
            })->orderBy('min_quantity')->get();
            foreach ($discounts as $discount) {
                $discount->setRelation('levels', $levels->where('user_id', $discount->user_id)
                    ->where('user_group_id', $discount->user_group_id)->where('brand_id', $discount->brand_id)
                    ->where('applies_to_everyone', $discount->applies_to_everyone)->values());
            }
        }

        return view('back.discount.index', compact('discounts'));
    }

    public function create()
    {
        return $this->form(new CustomerDiscount(['active' => true, 'min_quantity' => 1]));
    }

    public function edit(CustomerDiscount $discount)
    {
        return $this->form($discount);
    }

    private function form(CustomerDiscount $discount)
    {
        $groups = UserGroup::with('translation')->get();
        $brands = Brand::with('translation')->get()->sortBy('translation.title');
        $selectedUser = User::find(old('user_id', $discount->user_id));
        $levels = $discount->exists
            ? CustomerDiscount::where($this->target($discount))->orderBy('min_quantity')->get()->toArray()
            : [['min_quantity' => 1, 'percentage' => '', 'active' => true]];

        return view('back.discount.edit', compact('discount', 'groups', 'brands', 'selectedUser', 'levels'));
    }

    public function store(Request $request)
    {
        $legacy = !$request->has('tiers');
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $legacy) {
            $target = Arr::except($data, 'tiers');
            if (!$legacy && CustomerDiscount::where($target)->exists()) {
                throw ValidationException::withMessages(['brand_id' => 'Za ovaj odabir već postoje razine. Otvorite postojeći popust i dodajte razinu.']);
            }
            foreach ($data['tiers'] as $level) {
                CustomerDiscount::create($target + Arr::except($level, 'id'));
            }
        });
        return redirect()->route('discounts')->with('success', 'Količinski popust je spremljen.');
    }

    public function update(Request $request, CustomerDiscount $discount)
    {
        $legacy = !$request->has('tiers');
        $data = $this->validated($request);
        DB::transaction(function () use ($data, $discount, $legacy) {
            $target = Arr::except($data, 'tiers');
            // An older, already open form still updates only its original single level.
            if ($legacy) {
                $discount->update($target + Arr::except($data['tiers'][0], 'id'));
                return;
            }
            $existing = CustomerDiscount::where($this->target($discount))->lockForUpdate()->get()->keyBy('id');
            if (CustomerDiscount::where($target)->whereNotIn('id', $existing->keys())->exists()) {
                throw ValidationException::withMessages(['brand_id' => 'Za ovaj odabir već postoji popust. Uredite njegove razine.']);
            }
            $kept = [];
            foreach ($data['tiers'] as $index => $level) {
                $id = $level['id'] ?? null;
                if ($id && !$existing->has($id)) {
                    throw ValidationException::withMessages(['tiers.' . $index . '.id' => 'Razina ne pripada ovom popustu. Osvježite obrazac.']);
                }
                $rule = $id ? $existing->get($id) : new CustomerDiscount;
                $rule->fill($target + Arr::except($level, 'id'))->save();
                $kept[] = $rule->id;
            }
            CustomerDiscount::whereIn('id', $existing->keys())->whereNotIn('id', $kept)->delete();
        });
        return redirect()->route('discounts')->with('success', 'Količinski popust je ažuriran.');
    }

    public function destroy(Request $request, CustomerDiscount $discount)
    {
        if ($request->boolean('all_levels')) {
            CustomerDiscount::where($this->target($discount))->delete();
        } else {
            $discount->delete();
        }
        return redirect()->route('discounts')->with('success', 'Količinski popust je obrisan.');
    }

    public function users(Request $request)
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'page' => ['nullable', 'integer', 'min:1']]);
        $search = $data['q'] ?? '';
        $users = User::query()->where(function ($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%')->orWhere('email', 'like', '%' . $search . '%');
        })->orderBy('name')->paginate(25, ['id', 'name', 'email']);

        return response()->json([
            'results' => $users->getCollection()->map(fn ($user) => ['id' => $user->id, 'text' => $user->name . ' (' . $user->email . ')']),
            'pagination' => ['more' => $users->hasMorePages()],
        ]);
    }

    private function validated(Request $request): array
    {
        if (!$request->has('tiers')) {
            $request->merge(['tiers' => [$request->only('min_quantity', 'percentage', 'active')]]);
        }
        $data = $request->validate([
            'target' => ['required', Rule::in(['user', 'group', 'everyone'])],
            'user_id' => ['exclude_unless:target,user', 'required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'user_group_id' => ['exclude_unless:target,group', 'required', 'integer', 'exists:user_group,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'tiers' => ['required', 'array', 'min:1', 'max:100'],
            'tiers.*' => ['required', 'array:id,min_quantity,percentage,active'],
            'tiers.*.id' => ['nullable', 'integer', 'min:1', 'distinct'],
            'tiers.*.min_quantity' => ['required', 'integer', 'min:1', 'max:1000000', 'distinct'],
            'tiers.*.percentage' => ['required', 'numeric', 'min:0.01', 'max:100', 'decimal:0,2'],
            'tiers.*.active' => ['required', 'boolean'],
        ], [], [
            'user_id' => 'korisnik', 'user_group_id' => 'grupa korisnika', 'brand_id' => 'brend',
            'tiers.*.min_quantity' => 'minimalni broj komada razine', 'tiers.*.percentage' => 'postotak popusta razine',
        ]);

        return [
            'user_id' => $data['target'] === 'user' ? $data['user_id'] : null,
            'user_group_id' => $data['target'] === 'group' ? $data['user_group_id'] : null,
            'applies_to_everyone' => $data['target'] === 'everyone',
            'brand_id' => $data['brand_id'], 'tiers' => $data['tiers'],
        ];
    }

    private function target(CustomerDiscount $discount): array
    {
        return $discount->only('user_id', 'user_group_id', 'brand_id', 'applies_to_everyone');
    }
}
