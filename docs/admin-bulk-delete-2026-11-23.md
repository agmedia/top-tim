# Skupno brisanje artikala i akcija — 23. studenoga 2026.

Admin popisi **Artikli** i **Akcije** omogućuju odabir više redaka, označavanje svih stavki na trenutačnoj stranici i gumb „Obriši označene” uz broj odabranih stavki. Statusni prekidači nisu dio odabira. Odabir se ne prenosi na druge stranice. Potvrda prije slanja prikazuje broj stavki i upozorenje da se brisanje ne može poništiti; odustajanje ne šalje zahtjev. Tijekom slanja blokirano je ponovno pokretanje, a nakon pogreške moguće je pokušati ponovno. Uspjeh osvježava isti URL s postojećim filtrima i stranicom.

Zahtjevi koriste POST i CSRF zaštitu, najviše 50 različitih pozitivnih ID-jeva te postojeći pristup aktivnih administratora, superadministratora i urednika. Ovlasti, aktivnost i cijeli odabir ponovno se provjeravaju uz zaključavanje unutar jedne transakcije. Nepostojeći ID ili pogreška zaustavljaju cijeli odabir, bez djelomičnog brisanja. Stari GET put skupnog brisanja više ne mijenja podatke.

Brisanje artikala uklanja samo odabrane kataloške zapise i pripadajuće prijevode, slike u bazi, opcije, atribute i veze kategorija. Narudžbe, njihove stavke, povijesne cijene, objavljeni cjenici i fizičke fotografije ostaju sačuvani. Prikazi povijesti koriste spremljeni naziv, SKU, cijenu i fotografiju i kada artikl više ne postoji. Brisanje akcija čisti naslijeđene akcijske cijene samo proizvodima čiji `action_id` odgovara odabranim akcijama; druge akcije i cijene ostaju netaknute. Postojeće pojedinačno brisanje koristi isti postupak.

Objava ide kroz [verzioniranu Git skriptu](../scripts/deploy-admin-bulk-delete-20261123.sh) nakon pregleda, testova, commita i pusha. Baza je `434f4dd99b174d6055039896033cfbd93586433c`. Skripta zahtijeva točan `origin/master`, radi isključivo fast-forward, čuva `.env`, postojeće lokalne `config/mail.php` i korijenski `public/.htaccess`, osvježava rute i Blade predloške te provjerava POST rute. Ne instalira ovisnosti, ne pokreće migracije i ne briše nijedan proizvod ili akciju tijekom objave. Nema izravnog prijenosa izvornog koda ni automatskog povrata.

```sh
git fetch origin master
git show "$release:scripts/deploy-admin-bulk-delete-20261123.sh" | bash -s -- "$release"
```

Nakon objave provjeriti točan produkcijski HEAD i oba popisa, broj odabranih stavki, označavanje stranice i odustajanje od potvrde. Stvarno brisanje provjerava se na odvojenim sintetičkim podacima; produkcijske artikle i akcije ne brisati radi testiranja. Lozinke i postavke naplate ostaju iste.
