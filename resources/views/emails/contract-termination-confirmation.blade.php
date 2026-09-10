@extends('emails.layouts.base')

@section('content')
    <div style="padding:20px;font-family:Arial,sans-serif;color:#373f50;">
        <div style="margin:0 0 8px;color:#05519e;font-size:11px;font-weight:bold;letter-spacing:1.4px;text-transform:uppercase;">Top Tim</div>
        <h1 style="margin:0 0 18px;color:#2b3445;font-size:27px;line-height:1.2;">Izjava je zaprimljena</h1>
        <p style="color:#4b566b;line-height:1.65;">Poštovani/Poštovana {{ $data['full_name'] }}, potvrđujemo primitak vaše izjave o jednostranom raskidu za narudžbu ili račun <strong>{{ $data['order_number'] }}</strong>.</p>
        @if(! empty($data['reference']))<p style="color:#4b566b;line-height:1.65;">Referentna oznaka: <strong>{{ $data['reference'] }}</strong></p>@endif
        <p style="color:#4b566b;line-height:1.65;">Vrijeme primitka: <strong>{{ $data['submitted_at']->format('d.m.Y. H:i') }}</strong></p>
        <div style="margin:18px 0;padding:16px;border-left:4px solid #05519e;background:#f6f9fc;white-space:pre-wrap;">{{ $data['items'] }}</div>
        <p style="color:#4b566b;line-height:1.65;">Javit ćemo vam se na ovu e-mail adresu s uputama za povrat ili ako budu potrebni dodatni podaci. Sačuvajte ovu poruku kao dokaz primitka.</p>
        <p style="color:#4b566b;line-height:1.65;">Lijep pozdrav,<br><strong>Top Tim d.o.o.</strong></p>
    </div>
@endsection
