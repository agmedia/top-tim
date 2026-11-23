<?php

namespace Tests\Feature;

use App\Helpers\Image;
use App\Models\Back\Catalog\Product\ProductImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    private $disk;
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'filesystems.disks.products.url' => 'media/img/product/',
            'settings.image_size_ratio' => '600x600', 'settings.thumb_size_ratio' => '400x400']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('image');
            $table->timestamps();
        });
        Storage::fake('products');
        $this->disk = Storage::disk('products');
        $this->directory = $this->disk->path('');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_both_frontend_limits_accept_the_five_reported_images_over_two_megabytes(): void
    {
        $view = file_get_contents(resource_path('views/back/catalog/product/edit-photos.blade.php'));
        preg_match('/data-max-file-size="([0-9.]+)"/', $view, $existing);
        preg_match('/maxFileSize:\s*([0-9.]+)/', $view, $new);
        $this->assertNotEmpty($existing);
        $this->assertNotEmpty($new);
        foreach ([$existing[1], $new[1]] as $limit) {
            $this->assertEquals(Image::MAX_BYTES, (float) $limit * 1000000);
            foreach ([2071172, 2365098, 2268554, 2517568, 2644565] as $bytes) {
                $this->assertLessThanOrEqual((float) $limit, $bytes / 1000000);
            }
            $this->assertGreaterThan((float) $limit, (Image::MAX_BYTES + 1) / 1000000);
        }
        $this->assertStringContainsString('Najveća dopuštena veličina je 5 MB.', $view);
    }

    public function test_valid_png_over_two_megabytes_is_saved_as_decodable_jpeg_and_webp_variants(): void
    {
        $bytes = $this->png(1000, 800, 0);
        $this->assertGreaterThan(2000000, strlen($bytes));
        $this->assertSame($bytes, Image::makeImageFromBase($this->dataUri($bytes)));
        $path = Image::save('products', $this->payload($bytes), $this->resource());
        $this->assertSame(3, count($this->disk->allFiles()));
        foreach ([$path => 'image/jpeg', str_replace('.jpg', '.webp', $path) => 'image/webp',
            str_replace('.jpg', '-thumb.webp', $path) => 'image/webp'] as $file => $mime) {
            $this->disk->assertExists($file);
            $this->assertSame($mime, getimagesizefromstring($this->disk->get($file))['mime']);
        }
    }

    public function test_base64_upload_rejects_executable_svg_invalid_and_oversized_data(): void
    {
        foreach (['not a data URI', 'data:image/png;base64,***',
            'data:image/png;base64,' . base64_encode('<?php echo 1;'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg"/>'),
            $this->dataUri(str_repeat('x', Image::MAX_BYTES + 1))] as $input) {
            try {
                Image::makeImageFromBase($input);
                $this->fail('An invalid image was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('image', $exception->errors());
            }
        }
        $this->assertSame([], $this->disk->allFiles());
    }

    public function test_raster_dimensions_are_bounded_before_decoding_pixels(): void
    {
        $bytes = $this->png();
        foreach ([[5000, 4000], [8001, 1]] as [$width, $height]) {
            $ihdr = pack('NN', $width, $height) . substr($bytes, 24, 5);
            $header = substr($bytes, 0, 16) . $ihdr . pack('N', crc32('IHDR' . $ihdr)) . substr($bytes, 33);
            try {
                Image::makeImageFromBase($this->dataUri($header));
                $this->fail('An excessive raster was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('image', $exception->errors());
            }
        }
    }

    public function test_replacement_saves_new_files_and_then_deletes_old_variants_on_products_disk(): void
    {
        $this->oldImage();
        $image = new ProductImage();
        $image->setResource($this->resource());
        $this->assertTrue($image->replace(1, $this->payload($this->png())));
        $path = str_replace('media/img/product/', '', DB::table('product_images')->where('id', 1)->value('image'));
        $this->assertNotSame('7/old.jpg', $path);
        $this->disk->assertExists($path);
        foreach (['7/old.jpg', '7/old.webp', '7/old-thumb.webp'] as $old) {
            $this->disk->assertMissing($old);
        }
        $this->assertSame(3, count($this->disk->allFiles()));
    }

    public function test_failed_new_write_keeps_old_database_path_and_all_old_files(): void
    {
        $this->oldImage();
        $failing = Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $failing->shouldReceive('put')->once()->andReturn(false);
        $failing->shouldReceive('delete')->once()->with(Mockery::on(function ($paths) {
            return count($paths) === 3 && !in_array('7/old.jpg', $paths, true);
        }))->andReturn(true);
        Storage::shouldReceive('disk')->with('products')->andReturn($failing);
        $image = new ProductImage();
        $image->setResource($this->resource());
        try {
            $image->replace(1, $this->payload($this->png()));
            $this->fail('A failed image write was reported as successful.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Sliku nije moguće spremiti', $exception->getMessage());
        }
        $this->assertSame('media/img/product/7/old.jpg', DB::table('product_images')->where('id', 1)->value('image'));
        foreach (['7/old.jpg', '7/old.webp', '7/old-thumb.webp'] as $old) {
            $this->assertSame('old bytes', $this->disk->get($old));
        }
    }

    private function oldImage(): void
    {
        DB::table('product_images')->insert(['id' => 1, 'product_id' => 7, 'image' => 'media/img/product/7/old.jpg']);
        foreach (['7/old.jpg', '7/old.webp', '7/old-thumb.webp'] as $path) {
            $this->disk->put($path, 'old bytes');
        }
    }

    private function resource(): Model
    {
        return new class(['id' => 7]) extends Model {
            protected $guarded = [];
            public function getTranslationAttribute() { return (object) ['name' => 'Test photo']; }
        };
    }

    private function png(int $width = 10, int $height = 10, int $compression = 6): string
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagepng($image, null, $compression);
        $bytes = ob_get_clean();
        imagedestroy($image);
        return $bytes;
    }

    private function dataUri(string $bytes): string
    {
        return 'data:image/png;base64,' . base64_encode($bytes);
    }

    private function payload(string $bytes): array
    {
        return ['image' => json_encode(['output' => ['image' => $this->dataUri($bytes)]])];
    }
}
