<?php

// Exact legacy paths with verified, equivalent live destinations.
// Keep source paths without surrounding slashes and destinations local.
// Request query strings are preserved by LegacyCatalogRedirectController.
return [
    'adidas' => '/brand/adidas',
    'gift-card' => '/brand/gift-card',
    'givova' => '/brand/givova',
    'givova-rasprodaja' => '/brand/givova-rasprodaja',
    'joma' => '/brand/joma',
    'joma-box' => '/brand/joma-box',
    'joma-rasprodaja' => '/brand/joma-rasprodaja',
    'legea' => '/brand/legea',
    'macron' => '/brand/macron',
    'macron-box' => '/brand/macron-box',
    'macron-rasprodaja' => '/brand/macron-rasprodaja',
    'malfini' => '/brand/malfini',
    'molten' => '/brand/molten',
    'nike' => '/brand/nike',
    'puma' => '/brand/puma',
    'reusch' => '/brand/reusch',
    'roberto-sport' => '/brand/roberto-sport',
    'select' => '/brand/select',
    'sport-system' => '/brand/sport-system',
    'top-tim' => '/brand/top-tim',
    'tryumf' => '/brand/tryumf',
    'yakimasport' => '/brand/yakimasport',
    'zeus' => '/brand/zeus',
    'zeus-box' => '/brand/zeus-box',
    'zeus-rasprodaja' => '/brand/zeus-rasprodaja',

    // Categories retained from the previous shop, verified against the current catalogue.
    'shop/category/slobodno-vrijeme-73' => '/kategorija-proizvoda/slobodno-vrijeme',
    'shop/category/slobodno-vrijeme-hlace-78' => '/kategorija-proizvoda/slobodno-vrijeme/hlace',
    'shop/category/trening-stucne-65' => '/kategorija-proizvoda/dresovi/stucne',

    // Renamed products verified by model code or the same name and clearance category.
    'hr/kategorija-proizvoda/dresovi/trcanje/m1062' => '/kategorija-proizvoda/dresovi/nogomet/dres-gornji-dio-lipsia',
    'kategorija-proizvoda/dresovi/trcanje/m1062' => '/kategorija-proizvoda/dresovi/nogomet/dres-gornji-dio-lipsia',
    'hr/kategorija-proizvoda/dresovi/trcanje/m1184' => '/kategorija-proizvoda/dresovi/dres-gornji-dio-tahiti',
    'kategorija-proizvoda/dresovi/trcanje/m1184' => '/kategorija-proizvoda/dresovi/dres-gornji-dio-tahiti',
    'hr/kategorija-proizvoda/jakne/suskavci/k213' => '/kategorija-proizvoda/jakne/suskavci/suskavac-lima',
    'kategorija-proizvoda/jakne/suskavci/k213' => '/kategorija-proizvoda/jakne/suskavci/suskavac-lima',
    'hr/kategorija-proizvoda/jakne/suskavci/g032' => '/kategorija-proizvoda/jakne/zimske/jakna-bergen',
    'kategorija-proizvoda/jakne/suskavci/g032' => '/kategorija-proizvoda/jakne/zimske/jakna-bergen',
    'hr/kategorija-proizvoda/rasprodaja/dres-komplet-sticker' => '/kategorija-proizvoda/rasprodaja/dres-komplet-sticker-1761829167',
    'kategorija-proizvoda/rasprodaja/dres-komplet-sticker' => '/kategorija-proizvoda/rasprodaja/dres-komplet-sticker-1761829167',
];
