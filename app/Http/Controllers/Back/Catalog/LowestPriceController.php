<?php

namespace App\Http\Controllers\Back\Catalog;

use App\Http\Controllers\Controller;
use App\Services\LowestPriceCorrections;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LowestPriceController extends Controller
{
    public function update(Request $request, int $product, LowestPriceCorrections $corrections)
    {
        $user = $request->user();
        abort_unless($user && $user->isActive()
            && $user->roles()->whereIn('name', ['admin', 'superadmin'])->exists(), 403);
        $redirect = route('products.edit', $product).'#lowest-price';
        try {
            $corrections->save($product, $request->only('mode', 'price', 'reason'), $user);
        } catch (ValidationException $exception) {
            throw $exception->errorBag('lowestPrice')->redirectTo($redirect);
        }

        return redirect()->to($redirect)->with('success', $request->input('mode') === 'automatic'
            ? 'Najniža cijena ponovno koristi automatski izračun.'
            : 'Ručni ispravak najniže cijene je spremljen i vrijedi 30 dana.');
    }
}
