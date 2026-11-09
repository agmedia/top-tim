<?php

namespace App\Exports;

use App\Queries\AdminUserQuery;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class UsersExport
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        return AdminUserQuery::build($this->filters);
    }

    public function download(string $filename)
    {
        return response()->streamDownload(function () {
            $spreadsheet = $this->spreadsheet();
            try {
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function spreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Korisnici');
        $row = 1;
        foreach ($this->headings() as $column => $value) {
            $sheet->setCellValueExplicit([$column + 1, $row], $value, DataType::TYPE_STRING);
        }
        foreach ($this->query()->lazy(500) as $user) {
            $row++;
            foreach ($this->map($user) as $column => $value) {
                // Customer-controlled text must never become an Excel formula.
                $sheet->setCellValueExplicit([$column + 1, $row], (string) ($value ?? ''), DataType::TYPE_STRING);
            }
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:Z' . $row);

        return $spreadsheet;
    }

    public function map($user): array
    {
        $details = $user->details;
        $groupTitle = $details?->group?->translation?->title;

        return [
            $user->id,
            $user->name,
            $user->email,
            $user->email_verified_at ? 'Verified' : 'Unverified',
            $user->created_at?->toDateTimeString(),
            $user->updated_at?->toDateTimeString(),
            $details?->id,
            $details?->user_group_id,
            $groupTitle,
            $details?->fname,
            $details?->lname,
            $details?->address,
            $details?->zip,
            $details?->city,
            $details?->state,
            $details?->phone,
            $details?->avatar,
            $details?->bio,
            $details?->social,
            $details?->default_lang,
            $user->roles->pluck('name')->sort()->implode(', '),
            (int) ($details?->status ?? 0) === 1 ? 'Active' : 'Inactive',
            $details?->created_at?->toDateTimeString(),
            $details?->updated_at?->toDateTimeString(),
            $details?->oib,
            $details?->company,
        ];
    }

    public function headings(): array
    {
        return [
            'user_id', 'user_name', 'user_email', 'user_email_status', 'user_created_at', 'user_updated_at',
            'details_id', 'user_group_id', 'group_title', 'fname', 'lname', 'address', 'zip', 'city', 'state',
            'phone', 'avatar', 'bio', 'social', 'default_lang', 'role', 'details_status', 'details_created_at',
            'details_updated_at', 'oib', 'company',
        ];
    }
}
