<?php

namespace App\Http\Controllers\Back;

use App\Http\Controllers\Controller;
use App\Models\Back\UserGroup;
use App\Models\CustomerDiscount;
use App\Models\Front\Catalog\Brand;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        $discounts = CustomerDiscount::with(['user', 'group.translation', 'brand.translation'])
            ->orderByDesc('id')->paginate(30);

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

        return view('back.discount.edit', compact('discount', 'groups', 'brands', 'selectedUser'));
    }

    public function store(Request $request)
    {
        CustomerDiscount::create($this->validated($request));
        return redirect()->route('discounts')->with('success', 'Količinski popust je spremljen.');
    }

    public function update(Request $request, CustomerDiscount $discount)
    {
        $discount->update($this->validated($request));
        return redirect()->route('discounts')->with('success', 'Količinski popust je ažuriran.');
    }

    public function destroy(CustomerDiscount $discount)
    {
        $discount->delete();
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
        $data = $request->validate([
            'target' => ['required', Rule::in(['user', 'group'])],
            'user_id' => ['exclude_unless:target,user', 'required', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'user_group_id' => ['exclude_unless:target,group', 'required', 'integer', 'exists:user_group,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'min_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100', 'decimal:0,2'],
            'active' => ['required', 'boolean'],
        ], [], [
            'user_id' => 'korisnik', 'user_group_id' => 'grupa korisnika', 'brand_id' => 'brend',
            'min_quantity' => 'minimalni broj komada', 'percentage' => 'popust',
        ]);

        return [
            'user_id' => $data['target'] === 'user' ? $data['user_id'] : null,
            'user_group_id' => $data['target'] === 'group' ? $data['user_group_id'] : null,
            'brand_id' => $data['brand_id'], 'min_quantity' => $data['min_quantity'],
            'percentage' => $data['percentage'], 'active' => $data['active'],
        ];
    }
}
