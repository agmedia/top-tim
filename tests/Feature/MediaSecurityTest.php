<?php

namespace Tests\Feature;

use App\Http\Controllers\Back\Marketing\BlogController;
use App\Http\Controllers\Back\Marketing\ReceptiController;
use App\Services\SafeMediaImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MediaSecurityTest extends TestCase
{
    private $directory;
    private $image;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir() . '/top-tim-media-' . bin2hex(random_bytes(8));
        mkdir($this->directory . '/public/media/img', 0700, true);
        $this->app->usePublicPath($this->directory . '/public');
        config([
            'app.url' => 'https://shop.example.test',
            'settings.images_domain' => 'https://images.example.test',
            'database.default' => 'sqlite',
            'database.connections.sqlite.url' => null,
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'filesystems.disks.blog.root' => $this->directory . '/blog',
            'filesystems.disks.recepti.root' => $this->directory . '/recepti',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Schema::create('pages', function (Blueprint $table) { $table->id(); });
        $this->image = public_path('media/img/fixture.png');
        $image = imagecreatetruecolor(40, 30);
        imagepng($image, $this->image);
        imagedestroy($image);
    }

    protected function tearDown(): void
    {
        if ($this->directory) {
            (new Filesystem())->deleteDirectory($this->directory);
        }
        parent::tearDown();
    }

    public function test_image_cache_never_opens_a_caller_selected_loopback_url(): void
    {
        MediaHttpReadProbe::$opens = 0;
        stream_wrapper_unregister('http');
        stream_wrapper_register('http', MediaHttpReadProbe::class);
        try {
            $this->get('/cache/image?' . http_build_query(['src' => 'http://127.0.0.1/private.gif']))
                ->assertNotFound();
            $this->assertSame(0, MediaHttpReadProbe::$opens);
        } finally {
            stream_wrapper_restore('http');
        }
    }

    #[DataProvider('unsafeSources')]
    public function test_sources_cannot_escape_local_media(string $source): void
    {
        $this->assertNull(app(SafeMediaImage::class)->localPath($source));
    }

    public static function unsafeSources(): array
    {
        return [
            ['http://127.0.0.1/private.png'],
            ['https://169.254.169.254/latest/meta-data'],
            ['file:///etc/passwd'],
            ['php://filter/resource=../../.env'],
            ['//attacker.example.test/media/img/fixture.png'],
            ['https://shop.example.test@attacker.example.test/media/img/fixture.png'],
            ['https://shop.example.test.attacker.example.test/media/img/fixture.png'],
            ['/media/../private.png'],
            ['/media/%2e%2e/private.png'],
            ['/media/%252e%252e/private.png'],
            ['media\\..\\private.png'],
            ['/media/img/fixture.png' . "\0" . '.jpg'],
            ['/media-other/private.png'],
        ];
    }

    public function test_symbolic_links_cannot_expose_images_outside_the_media_root(): void
    {
        copy($this->image, $this->directory . '/private.png');
        symlink($this->directory . '/private.png', public_path('media/img/private.png'));
        $this->get('/cache/image?src=media/img/private.png')->assertNotFound();
    }

    #[DataProvider('legitimateSources')]
    public function test_approved_media_urls_are_loaded_locally_and_can_be_resized(string $source): void
    {
        $response = $this->get('/cache/thumb?' . http_build_query(['src' => $source, 'size' => '10x10']))
            ->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $dimensions = getimagesizefromstring($response->getContent());
        $this->assertLessThanOrEqual(10, $dimensions[0]);
        $this->assertLessThanOrEqual(10, $dimensions[1]);
    }

    public static function legitimateSources(): array
    {
        return [['media/img/fixture.png'], ['/media/img/fixture.png'],
            ['https://shop.example.test/media/img/fixture.png'],
            ['https://images.example.test/media/img/fixture.png']];
    }

    #[DataProvider('oversizedRequests')]
    public function test_requested_output_sizes_are_bounded($size): void
    {
        $this->getJson('/cache/thumb?' . http_build_query(['src' => 'media/img/fixture.png', 'size' => $size]))
            ->assertUnprocessable();
    }

    public static function oversizedRequests(): array
    {
        return [['999999x999999'], ['1601x1'], ['0x100'], [['100', '100']]];
    }

    public function test_source_pixel_limits_are_checked_before_decoding(): void
    {
        // A small PNG header advertises excessive dimensions; no huge image is allocated.
        $data = "\x89PNG\r\n\x1a\n";
        $header = pack('NNCCCCC', 100000, 100000, 8, 2, 0, 0, 0);
        $data .= pack('N', strlen($header)) . 'IHDR' . $header . pack('N', crc32('IHDR' . $header));
        $data .= pack('N', 0) . 'IEND' . pack('N', crc32('IEND'));
        file_put_contents($this->image, $data);
        $this->getJson('/cache/image?src=media/img/fixture.png')->assertUnprocessable();
    }

    #[DataProvider('uploadControllers')]
    public function test_non_images_and_svg_are_rejected_before_public_storage(string $controller, string $method, string $disk): void
    {
        foreach ([['audit.php', 'text/plain', '<?php /* inert */'],
            ['audit.svg', 'image/svg+xml', '<svg xmlns="http://www.w3.org/2000/svg"><script>window.audit=1</script></svg>']] as [$name, $mime, $content]) {
            $path = $this->directory . '/upload';
            file_put_contents($path, $content);
            $request = Request::create('/upload', 'POST', [], [], [
                'upload' => new UploadedFile($path, $name, $mime, null, true),
            ]);
            try {
                (new $controller())->$method($request);
                $this->fail('An executable or SVG upload was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('upload', $exception->errors());
            }
            $this->assertSame([], \Storage::disk($disk)->allFiles());
        }
    }

    public static function uploadControllers(): array
    {
        return [[BlogController::class, 'uploadBlogImage', 'blog'], [ReceptiController::class, 'uploadReceptiImage', 'recepti']];
    }

    public function test_image_payload_is_reencoded_under_a_random_safe_name_and_strips_trailing_code(): void
    {
        file_put_contents($this->image, file_get_contents($this->image) . '<?php audit-inert-marker ?>');
        $request = Request::create('/upload', 'POST', ['blog_id' => '7'], [], [
            'upload' => new UploadedFile($this->image, 'payload.png', 'image/png', null, true),
        ]);
        $response = (new BlogController())->uploadBlogImage($request)->getData(true);
        $files = \Storage::disk('blog')->allFiles();
        $this->assertTrue($response['uploaded']);
        $this->assertMatchesRegularExpression('/^7\/[A-Za-z0-9]{32}\.png$/D', $files[0]);
        $stored = \Storage::disk('blog')->get($files[0]);
        $this->assertStringNotContainsString('audit-inert-marker', $stored);
        $this->assertSame('image/png', getimagesizefromstring($stored)['mime']);
    }

    public function test_upload_folder_cannot_contain_traversal(): void
    {
        $request = Request::create('/upload', 'POST', ['blog_id' => '../other'], [], [
            'upload' => new UploadedFile($this->image, 'image.png', 'image/png', null, true),
        ]);
        try {
            (new BlogController())->uploadBlogImage($request);
            $this->fail('A caller-controlled path was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('blog_id', $exception->errors());
        }
        $this->assertSame([], \Storage::disk('blog')->allFiles());
    }
}

class MediaHttpReadProbe
{
    public $context;
    public static $opens = 0;
    private $offset = 0;
    private $body;

    public function stream_open($path, $mode, $options, &$openedPath): bool
    {
        self::$opens++;
        $this->body = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return true;
    }

    public function stream_read($count): string
    {
        $result = substr($this->body, $this->offset, $count);
        $this->offset += strlen($result);
        return $result;
    }

    public function stream_eof(): bool { return $this->offset >= strlen($this->body); }
    public function stream_stat(): array { return []; }
}
