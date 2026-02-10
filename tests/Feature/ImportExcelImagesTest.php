<?php

namespace Tests\Feature;

use App\Models\Back\Settings\Api\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportExcelImagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_imports_product_and_binds_option_images_and_ids()
    {
        // 1) Seed minimalne dependent tablice
        // Options: boja(parent), velicina(child)
        $colorId = DB::table('options')->insertGetId([
            'group'       => 'boja',
            'type'        => 'select',
            'value'       => 'Boja',
            'value_opt'   => null,
            'option_sku'  => '2',
            'data'        => null,
            'sort_order'  => 0,
            'status'      => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        DB::table('options_translations')->insert([
            'option_id'   => $colorId,
            'lang'        => 'hr',
            'group_title' => 'Boja',
            'title'       => 'Crna',
            'description' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $sizeId = DB::table('options')->insertGetId([
            'group'       => 'velicina',
            'type'        => 'select',
            'value'       => 'Veličina',
            'value_opt'   => null,
            'option_sku'  => 'S/M',
            'data'        => null,
            'sort_order'  => 0,
            'status'      => 1,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        DB::table('options_translations')->insert([
            'option_id'   => $sizeId,
            'lang'        => 'hr',
            'group_title' => 'Veličina',
            'title'       => 'S/M',
            'description' => null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // 2) Priprema upload foldera i slika
        $baseDir = storage_path('app/imports/images/test_' . Str::random(6));
        @mkdir($baseDir, 0777, true);

        // Glavna slika -> striktno SKU.jpg
        $sku = 'LEG00009';
        file_put_contents($baseDir . '/' . $sku . '.jpg', random_bytes(128)); // dummy bin

        // Opcijska slika s '/', ugniježđeno: "LEG00009-2-S/M.jpg"
        // File sistem ne voli '/', pa napravimo folder "LEG00009-2-S" i datoteku "M.jpg"
        @mkdir($baseDir . '/LEG00009-2-S', 0777, true);
        file_put_contents($baseDir . '/LEG00009-2-S/M.jpg', random_bytes(128));

        // 3) Excel rows (minimalni skup -> header + 1 product + 1 option red)
        $rows = [
            ['Šifra','Šifra opcije','Barkod','Naziv','Opis','Slug','Meta naziv','Meta opis','Cijena','Količina','PDV','Aktivan','Slike','Proizvođač','Primarna skupina','Sekundarna skupina','Tablica veličina','Materijal','Spol','Tip rukava','Kroj','Dimenzije','Dodatna kategorizacija','Naziv opcije','Vrijednost opcije'],
            [$sku, '', '', 'ČARAPA ANTI-SKLIZNA', '', '', '', '', 100, 10, 25, 1, '', '', '', '', '', '94% Poliester, 6% Elastin', '', '', '', '', ''],
            [$sku, 'LEG00009-2-S/M', '', '', '', '', '', '', 100, 5, '', 1, '', '', '', '', '', '', '', '', '', '', '', 'Boja', 'S/M'],
        ];

        // 4) Pozovi import
        $export = new Export();
        $payload = [
            'method'     => 'import-from-excel',
            'images_dir' => $baseDir,
        ];
        $resp = $export->process($payload, $rows);

        // 5) Provjere
        $prodId = DB::table('products')->where('sku', $sku)->value('id');
        $this->assertNotEmpty($prodId, 'Proizvod nije kreiran');

        // Glavna slika (product_images.default = 1) + postoji products.image
        $mainImg = DB::table('product_images')->where('product_id', $prodId)->where('default', 1)->first();
        $this->assertNotNull($mainImg, 'Glavna slika nije spremljena');
        $this->assertNotEmpty(DB::table('products')->where('id', $prodId)->value('image'), 'Products.image nije postavljen');

        // product_option red i image_id != 0
        $po = DB::table('product_option')->where('product_id', $prodId)->where('sku', 'LEG00009-2-S/M')->first();
        $this->assertNotNull($po, 'product_option red nedostaje');
        $this->assertTrue((int)$po->image_id > 0, 'product_option.image_id nije povezan');

        // postoji product_images red s option_id = parentId (boja)
        $imgWithParent = DB::table('product_images')->where('product_id', $prodId)->where('option_id', $colorId)->first();
        $this->assertNotNull($imgWithParent, 'Opcijska slika nije spremljena s option_id = parent (boja)');
    }
}