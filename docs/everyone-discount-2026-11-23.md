# Količinski popust za sve kupce — 23. studenoga 2026.

Obrazac količinskog popusta u polju „Popust vrijedi za” nudi **Svima**, uz postojeće odabire korisnika i grupe. Taj odabir uključuje goste i sve prijavljene kupce te ne traži korisnika ni grupu. Brend, količinski pragovi i aktivnost svake razine ostaju zasebno podesivi. Na popisu je javni odabir jasno označen.

Javni opseg sprema se izričitom oznakom `applies_to_everyone`; postojeći zapisi dobivaju `false`. Prazan ili izgubljen korisnik/grupa ne pretvara postojeće pravilo u javni popust. Spremanje izvodi oznaku iz validiranog odabira, čisti nepripadajuće ID-jeve te razdvaja javna, korisnička, grupna i stara nedodijeljena pravila pri uređivanju svih razina i brisanju. Pristup izmjenama ostaje ograničen na aktivne administratore i superadministratore uz postojeću prijavu i CSRF zaštitu.

Katalog i košarica primjenjuju javne razine i gostima. Za prijavljenog kupca uzimaju se u obzir i njegova postojeća dopuštena privatna pravila. Zadržava se najpovoljnija cijena, bez zbrajanja popusta na artikl. Prag se računa zbrojem komada istog brenda kroz artikle i varijante, a snimljene stavke narudžbe koriste isti izračun. Privatni podaci o korisnicima i grupama ne ulaze u javne razine.

Javni popust koji počinje od jednog komada sudjeluje i u budućim javnim zapisima cijena te pri izradi novog XML cjenika. Viši količinski pragovi i privatna pravila ne ulaze u pojedinačnu javnu cijenu. Postojeća povijest, objavljeni cjenici i ručni ispravci najniže cijene ostaju sačuvani. Objava ne pokreće snimanje cijena, izdavanje novog cjenika niti pretvara postojeća pravila u „Svima”.

Objava se provodi kroz [Git skriptu](../scripts/deploy-everyone-discount-20261123.sh), nakon pregleda, testova, commita i pusha. Baza je `63b753f9c420e3e7f69beea17ac480d2ec9170b4`. Skripta provjerava točan privatni `origin/master`, radi isključivo fast-forward i primjenjuje samo `2026_11_23_190000_add_everyone_to_customer_discounts.php`. Nakratko uključuje održavanje prije promjene koda, zatim provjerava migraciju i rute te vraća stranicu u rad. `.env`, postojeći lokalni `config/mail.php` i `public/.htaccess` provjeravaju se prije i nakon objave. Ne mijenjaju se lozinke ni podaci za naplatu.

```sh
git fetch origin master
git show "$release:scripts/deploy-everyone-discount-20261123.sh" | bash -s -- "$release"
```

Ako objava stane tijekom održavanja, pregledati problem i nastaviti unaprijed istu provjerenu verziju istom skriptom; nema automatskog povrata. Skripta prepoznaje samo vlastito označeno održavanje. Povrat nove migracije odbija ukloniti oznaku ako već postoji ijedan javni zapis, čime se izbjegava gubitak značenja postojećih pravila.

Nakon objave potvrditi produkcijski commit, završetak održavanja, opciju i promjenu vidljivosti polja u adminu te javni katalog i košaricu. Obračun i spremanje testiraju se na sintetičkim podacima; ne mijenjati stvarna pravila ili stvarati stvarne narudžbe radi provjere.
