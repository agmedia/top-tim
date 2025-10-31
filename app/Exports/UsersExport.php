<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private ?string $search = null) {}

    public function query()
    {
        // Eager load da izbjegnemo N+1 i da imamo sve detalje pri ruci
        return User::query()
            ->with(['details.group.translation'])
            ->when($this->search, function ($q, $s) {
                $q->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhereHas('details', function ($q) use ($s) {
                            $q->where('fname', 'like', "%{$s}%")
                                ->orWhere('lname', 'like', "%{$s}%")
                                ->orWhere('company', 'like', "%{$s}%")
                                ->orWhere('oib', 'like', "%{$s}%")
                                ->orWhere('city', 'like', "%{$s}%");
                        });
                });
            });
    }

    public function map($user): array
    {
        $d = $user->details; // može biti null
        $groupTitle = optional(optional($d)->Group->translation ?? null)->title;

        return [
            // --- USERS (osnovno)
            $user->id,
            $user->name,
            $user->email,
            $user->email_verified_at ? 'Active' : 'Inactive',
            optional($user->created_at)?->toDateTimeString(),
            optional($user->updated_at)?->toDateTimeString(),

            // --- USER_DETAILS (sve iz tablice)
            optional($d)->id,
            optional($d)->user_group_id,
            $groupTitle,
            optional($d)->fname,
            optional($d)->lname,
            optional($d)->address,
            optional($d)->zip,
            optional($d)->city,
            optional($d)->state,
            optional($d)->phone,
            optional($d)->avatar,
            optional($d)->bio,
            optional($d)->social,
            optional($d)->default_lang,
            optional($d)->role,
            isset($d->status) ? ((int)$d->status === 1 ? 'Active' : 'Inactive') : null,
            optional($d)->created_at?->toDateTimeString(),
            optional($d)->updated_at?->toDateTimeString(),
            optional($d)->oib,
            optional($d)->company,
        ];
    }

    public function headings(): array
    {
        return [
            // --- USERS
            'user_id',
            'user_name',
            'user_email',
            'user_email_status',
            'user_created_at',
            'user_updated_at',
            // --- USER_DETAILS
            'details_id',
            'user_group_id',
            'group_title',
            'fname',
            'lname',
            'address',
            'zip',
            'city',
            'state',
            'phone',
            'bio',
            'social',
            'default_lang',
            'role',
            'details_status',
            'details_created_at',
            'details_updated_at',
            'oib',
            'company',
        ];
    }
}
