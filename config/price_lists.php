<?php

return [
    'reference_date' => '2026-09-10',
    'timezone' => 'Europe/Zagreb',
    'enabled' => env('PRICE_LISTS_ENABLED', true),
    'oib' => '20925110769',
    // YakimaSport's catalogue specifies this EAN for Rebounder 100011.
    'verified_ean' => ['YKM100011' => '5903137554086'],
    'archive_days' => 30,
    'store_name' => 'Top Tim d.o.o.',
    'store_address' => 'Put Gvozdenova 283, 22000 Šibenik',
    'store_code' => 'webshop',
    'disk' => 'local',
];
