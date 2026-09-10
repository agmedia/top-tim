@extends('emails.layouts.base')

@section('content')
    <div style="padding:20px;font-family:Arial,sans-serif;color:#373f50;">
        <div style="margin:0 0 8px;color:#05519e;font-size:11px;font-weight:bold;letter-spacing:1.4px;text-transform:uppercase;">Administracija · raskid ugovora</div>
        <h1 style="margin:0 0 18px;color:#2b3445;font-size:27px;line-height:1.2;">Nova izjava o raskidu</h1>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border:1px solid #dae1e7;background:#f6f9fc;font-size:14px;line-height:1.45;">
            @if(! empty($data['reference']))<tr><td><strong>Referenca</strong></td><td>{{ $data['reference'] }}</td></tr>@endif
            <tr><td><strong>Potrošač</strong></td><td>{{ $data['full_name'] }}</td></tr>
            <tr><td><strong>E-mail</strong></td><td><a href="mailto:{{ $data['email'] }}">{{ $data['email'] }}</a></td></tr>
            <tr><td><strong>Telefon</strong></td><td>{{ $data['phone'] ?: '—' }}</td></tr>
            <tr><td><strong>Adresa</strong></td><td>{{ $data['address'] }}, {{ $data['postal_code'] }} {{ $data['city'] }}, {{ $data['country'] }}</td></tr>
            <tr><td><strong>Broj narudžbe/računa</strong></td><td>{{ $data['order_number'] }}</td></tr>
            <tr><td><strong>Izjava poslana</strong></td><td>{{ $data['submitted_at']->format('d.m.Y. H:i') }}</td></tr>
            <tr><td><strong>Datum narudžbe</strong></td><td>{{ $data['order_date'] ?: '—' }}</td></tr>
            <tr><td><strong>Datum primitka</strong></td><td>{{ $data['received_date'] ?: '—' }}</td></tr>
            <tr><td><strong>IBAN</strong></td><td>{{ $data['iban'] ?: '—' }}</td></tr>
        </table>
        <h2 style="margin:24px 0 8px;color:#2b3445;font-size:17px;">Roba na koju se raskid odnosi</h2>
        <div style="padding:16px;border-left:4px solid #05519e;background:#f6f9fc;white-space:pre-wrap;">{{ $data['items'] }}</div>
        <p style="margin:22px 0 0;color:#4b566b;line-height:1.6;">Potrošač je potvrdio nedvosmislenu izjavu da jednostrano raskida navedeni ugovor.</p>
    </div>
@endsection
