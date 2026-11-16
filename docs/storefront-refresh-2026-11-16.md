# Dostava, sadržaj, FAN SHOP i veličine — 16.11.2026.

Rokovi: rasprodaja 3–7 radnih dana; svi ostali artikli 15–30. Footer ima radno vrijeme Pon–Pet 08:00–15:00 umjesto adrese. Kontakt i footer koriste isti HPB račun, bez OTP podataka. Isključeno je prikazivanje naslijeđene sekundarne HRK valute, a pomoćno formatiranje cijena koristi EUR i ispravne decimale.

Jednokratna naredba mijenja samo dvije hrvatske informativne stranice, dva FAQ odgovora, redoslijed glavnih kategorija i dodaje FAN SHOP. Ne dira proizvode, narudžbe, korisnike ili prodajne cijene. Nema migracije sheme. Izvorni sadržaj i kategorije spremaju se u privatni `storage/app/storefront-backups`. Marker u `settings` sprečava ponovno prepisivanje naknadnih admin izmjena. Neprepoznate ili duplicirane stranice zaustavljaju primjenu prije upisa.

```sh
php artisan storefront:refresh-content
php artisan storefront:refresh-content --apply
php artisan config:cache
php artisan view:cache
```

Nakon objave provjeriti `/kontakt`, `/faq`, `/info/informacije-o-dostavi`, `/info/opci-uvjeti-poslovanja`, `/kategorija-proizvoda/fan-shop`, navigaciju i filter veličina. Ako je potreban povrat sadržaja, iz privatnog JSON-a vratiti samo `before` retke nakon usporedbe s trenutačnim sadržajem; ne prepisivati kasnije korisničke izmjene. Kategoriju s naknadno dodanim klubovima ili artiklima ne brisati automatski.

## Uređivanje klubova

Admin → Katalog → Kategorije → nova kategorija. Kao glavnu kategoriju odabrati FAN SHOP, unijeti naziv kluba, učitati grb (JPG, PNG ili WebP do 5 MB), uključiti kategoriju i spremiti. Grb se prikazuje bez rezanja u pregledu klubova. Proizvodima u uređivanju pridružiti FAN SHOP i potkategoriju kluba. Redoslijed i status uređuju se kao za druge kategorije. Spremljene izmjene odmah osvježavaju navigacijsku predmemoriju. Skriveni klubovi nisu u javnom popisu.

Veličine se sortiraju prvo numerički (uključujući 10 iza 9), zatim 6XS do 6XL. XXS/XXXL se pri sortiranju tretiraju kao 2XS/3XL; nazivi i ID-evi u katalogu ostaju isti. Ostale oznake dolaze iza standardnih veličina.

## Početna vrijednost najniže cijene i daljnja evidencija

Na izričit zahtjev trenutačna javna cijena upisuje se kao početna vrijednost proizvodima bez evidencije. Akcijska cijena ulazi u zapis ako je dostupna svim kupcima; osobni, grupni, prijavljeni i kuponski popusti ne ulaze. Naredba `prices:record --missing-only` ne prepisuje postojeće iznose ni datume. Prvi zapis ima izvor `current_price_seed` i stvarno vrijeme upisa, bez datiranja unatrag. Prodajne i sidrene cijene ostaju iste.

Prikaz je „Najniža zabilježena cijena” uz „Evidencija od [datum]” dok nema 30 dana evidencije. Daljnje snimanje raspoređeno je svakih 15 minuta: promjena dodaje novi zapis, nepromijenjena cijena osvježava vrijeme posljednje provjere. Prikazuje se minimum iz javnih cijena zabilježenih u posljednjih 30 dana. Zapisi se ne brišu. To su periodične snimke, ne zajamčena evidencija svake promjene između provjera niti potvrđena referentna cijena prije početka sniženja. Potpuno pravno rješenje prethodnih 30 dana zahtijeva ranije provjerene podatke i događaje početka akcija.

```sh
php artisan migrate --path=database/migrations/2026_11_16_180000_create_product_price_observations.php --force
php artisan prices:record --missing-only
php artisan prices:record
```

Provjeriti postojeći cron koji pokreće `artisan schedule:run` svake minute. Nova tablica `product_price_observations` odvojena je od kataloga i sidrenih cijena. Za povrat aplikacije zadržati tablicu i podatke; ne pokretati destruktivni rollback nakon prikupljanja evidencije.

Uvjeti su jezično uređeni, uklonjena su stara kunska preračunavanja i zastarjeli myPOS/navodi o automatskoj privoli; ispravljeni su opći tekstovi o povratu, prigovorima i isključenju odgovornosti. Ovo nije potvrda potpune pravne usklađenosti. Zasebna stranica privatnosti i stvarna tehnička privola za analitiku/marketing zahtijevaju poseban pregled. Za konačnu pravnu redakciju provjeriti i posebne poslovne postupke, personalizaciju i cijenu povrata glomazne robe.

Izvori provjere:
- https://narodne-novine.nn.hr/clanci/sluzbeni/2022_02_19_203.html (povrat, prigovori)
- https://narodne-novine.nn.hr/clanci/sluzbeni/2026_06_59_728.html (izmjene, referentna cijena prije sniženja)
- https://www.hpb.hr/hr/osnovne-informacije/118 (naziv banke i SWIFT; IBAN trgovca preuzet iz postojećeg footera)
