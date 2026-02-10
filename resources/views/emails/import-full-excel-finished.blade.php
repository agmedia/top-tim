@extends('emails.layouts.base')

@section('content')
    @php
        $statusLabel = $success ? 'USPJEŠNO' : 'GREŠKA';
        $statusColor = $success ? '#1f7a1f' : '#a50000';
        $plainSummary = trim(preg_replace('/\s+/', ' ', strip_tags((string)$summary)));
    @endphp

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
        <tr>
            <td class="ag-mail-tableset">
                <h3 style="margin:0 0 8px 0;">Import Full Excel — završni izvještaj</h3>
                <p style="margin:0;">
                    Status:
                    <span style="display:inline-block;padding:2px 8px;border-radius:3px;background-color:{{ $statusColor }};color:#ffffff;font-weight:600;">
                        {{ $statusLabel }}
                    </span>
                </p>
            </td>
        </tr>

        <tr>
            <td class="ag-mail-tableset">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border:1px solid #e5e5e5;">
                    <tr>
                        <td style="padding:8px 10px;width:160px;background:#fafafa;"><strong>Vrijeme</strong></td>
                        <td style="padding:8px 10px;">{{ now()->format('d.m.Y H:i:s') }}</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 10px;width:160px;background:#fafafa;"><strong>Datoteka</strong></td>
                        <td style="padding:8px 10px;"><code>{{ $filePath }}</code></td>
                    </tr>
                    <tr>
                        <td style="padding:8px 10px;width:160px;background:#fafafa;"><strong>Sažetak</strong></td>
                        <td style="padding:8px 10px;">{{ $plainSummary }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection

