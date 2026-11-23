<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->isActive()
            && $user->roles()->whereIn('name', ['superadmin', 'admin', 'editor'])->exists();
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'list', 'min:1', 'max:50'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Odaberite barem jednu stavku.',
            'ids.array' => 'Odabir stavki nije valjan.',
            'ids.list' => 'Odabir stavki nije valjan.',
            'ids.min' => 'Odaberite barem jednu stavku.',
            'ids.max' => 'Odjednom možete izbrisati najviše 50 stavki.',
            'ids.*.required' => 'Odabrana stavka nije valjana.',
            'ids.*.integer' => 'Odabrana stavka nije valjana.',
            'ids.*.min' => 'Odabrana stavka nije valjana.',
            'ids.*.distinct' => 'Ista stavka ne može biti odabrana više puta.',
        ];
    }
}
