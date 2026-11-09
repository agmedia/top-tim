<?php

namespace Tests\Feature;

use App\Models\Back\Settings\Api\Export;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportExcelImagesTest extends TestCase
{
    private $imagesDirectory;
    private $productsDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'app.locale' => 'hr',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        app()->setLocale('hr');
        app('laravellocalization')->setLocale('hr');

        // The import must never write through the configured real public/media disk.
        Storage::fake('products');
        $this->productsDirectory = Storage::disk('products')->path('');
        $this->imagesDirectory = sys_get_temp_dir() . '/top-tim-import-images-' . bin2hex(random_bytes(8));
        mkdir($this->imagesDirectory, 0700, true);

        require_once database_path('migrations/2020_01_02_223851_create_authors_table.php');
        require_once database_path('migrations/2020_01_02_223911_create_categories_table.php');
        require_once database_path('migrations/2020_01_02_223921_create_products_table.php');
        (new \CreateAuthorsTable())->up();
        (new \CreateCategoriesTable())->up();
        $attributes = require database_path('migrations/2020_01_02_223900_create_attributtes_table.php');
        $attributes->up();
        Schema::create('options', function (Blueprint $table) {
            $table->id();
            foreach (['group', 'type', 'value', 'value_opt', 'option_sku', 'data'] as $field) {
                $table->string($field)->nullable();
            }
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('options_translations', function (Blueprint $table) {
            $table->id();
            $table->integer('option_id');
            $table->string('lang');
            $table->string('group_title');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        (new \CreateProductsTable())->up();
        Schema::table('product_images', function (Blueprint $table) { $table->integer('option_id')->nullable(); });
        Schema::table('product_option', function (Blueprint $table) { $table->integer('image_id')->default(0); });
    }

    protected function tearDown(): void
    {
        foreach ([$this->imagesDirectory, $this->productsDirectory] as $directory) {
            if ($directory) { (new Filesystem())->deleteDirectory($directory); }
        }
        parent::tearDown();
    }

    public function test_it_imports_product_and_binds_option_images_and_ids(): void
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
        $baseDir = $this->imagesDirectory;

        // Glavna slika -> striktno SKU.jpg
        $sku = 'LEG00009';
        $fixture = imagecreatetruecolor(32, 32);
        imagejpeg($fixture, $baseDir . '/' . $sku . '.jpg');

        // Opcijska slika s '/', ugniježđeno: "LEG00009-2-S/M.jpg"
        // File sistem ne voli '/', pa napravimo folder "LEG00009-2-S" i datoteku "M.jpg"
        mkdir($baseDir . '/LEG00009-2-S', 0700, true);
        imagejpeg($fixture, $baseDir . '/LEG00009-2-S/M.jpg');
        imagedestroy($fixture);

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

        $stored = Storage::disk('products')->allFiles();
        $this->assertNotEmpty($stored, 'Import nije spremio stvarne slike na izolirani disk.');
        foreach ($stored as $path) {
            $this->assertNotFalse(getimagesizefromstring(Storage::disk('products')->get($path)));
        }
    }
}
