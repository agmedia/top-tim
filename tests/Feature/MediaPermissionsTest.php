<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MediaPermissionsTest extends TestCase
{
    private string $temporary;
    private const DISKS = ['category', 'sizeguide', 'publisher', 'author', 'products', 'blog', 'recepti', 'page', 'widget', 'qr'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporary = sys_get_temp_dir() . '/top-tim-media-permissions-' . bin2hex(random_bytes(6));
        mkdir($this->temporary, 0700);
        $this->app->usePublicPath($this->temporary . '/public');
        foreach (self::DISKS as $disk) {
            $root = public_path('media/img/' . $disk);
            mkdir($root, 0755, true);
            config(['filesystems.disks.' . $disk . '.root' => $root]);
            Storage::forgetDisk($disk);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::DISKS as $disk) {
            Storage::forgetDisk($disk);
        }
        if (isset($this->temporary)) {
            $this->removeTree($this->temporary);
        }
        parent::tearDown();
    }

    public function test_new_public_media_directories_and_files_use_public_modes_with_the_web_umask(): void
    {
        $previous = umask(0022);
        try {
            foreach (self::DISKS as $disk) {
                $this->assertTrue(Storage::disk($disk)->put('17/new.jpeg', 'isolated image fixture'));
                $this->assertSame(0755, $this->mode(public_path('media/img/' . $disk . '/17')));
                $this->assertSame(0644, $this->mode(public_path('media/img/' . $disk . '/17/new.jpeg')));
            }
        } finally {
            umask($previous);
        }
        foreach (['local', 'assets', 'view'] as $disk) {
            $this->assertNotSame('public', config('filesystems.disks.' . $disk . '.visibility'));
        }
    }

    public function test_default_dry_run_preserves_directory_file_modes_and_bytes(): void
    {
        $path = $this->image('category', '17', 'crest.jpeg');
        $hash = hash_file('sha256', $path);
        $this->assertSame(0, Artisan::call('media:repair-permissions', ['disk' => 'category', 'ids' => ['17']]));
        $this->assertStringContainsString('Probna provjera', Artisan::output());
        $this->assertSame(0700, $this->mode(dirname($path)));
        $this->assertSame(0600, $this->mode($path));
        $this->assertSame($hash, hash_file('sha256', $path));
    }

    public function test_failed_public_image_write_throws_instead_of_returning_false(): void
    {
        file_put_contents(public_path('media/img/category/17'), 'a file blocks the directory');
        $this->expectException(\League\Flysystem\UnableToCreateDirectory::class);
        Storage::disk('category')->put('17/crest.jpg', 'cannot be written');
    }

    public function test_apply_changes_only_selected_id_directories_and_direct_image_file_modes(): void
    {
        $hashes = [];
        foreach (['jpg', 'JPEG', 'png', 'webp', 'gif'] as $extension) {
            $path = $this->image('products', '17', 'photo.' . $extension);
            $hashes[$path] = hash_file('sha256', $path);
        }
        $unselected = $this->image('products', '18', 'other.jpg');
        $selected = dirname(array_key_first($hashes));
        file_put_contents($selected . '/private.txt', 'leave private');
        chmod($selected . '/private.txt', 0600);
        file_put_contents($selected . '/.hidden.jpg', 'leave hidden');
        chmod($selected . '/.hidden.jpg', 0600);
        mkdir($selected . '/nested', 0700);
        file_put_contents($selected . '/nested/photo.jpg', 'leave nested');
        chmod($selected . '/nested/photo.jpg', 0600);
        $outside = $this->temporary . '/outside.jpg';
        file_put_contents($outside, 'leave linked target');
        chmod($outside, 0600);
        symlink($outside, $selected . '/linked.jpg');

        $this->assertSame(0, Artisan::call('media:repair-permissions', ['disk' => 'products', 'ids' => ['17'], '--apply' => true]));
        $this->assertSame(0755, $this->mode($selected));
        foreach ($hashes as $path => $hash) {
            $this->assertSame(0644, $this->mode($path));
            $this->assertSame($hash, hash_file('sha256', $path));
        }
        $this->assertSame(0700, $this->mode(dirname($unselected)));
        foreach ([$unselected, $selected . '/private.txt', $selected . '/.hidden.jpg', $selected . '/nested/photo.jpg', $outside] as $path) {
            $this->assertSame(0600, $this->mode($path));
        }
        $this->assertSame(0700, $this->mode($selected . '/nested'));
        $this->assertTrue(is_link($selected . '/linked.jpg'));
        $this->assertSame('leave linked target', file_get_contents($outside));
        $this->assertSame(0, Artisan::call('media:repair-permissions', ['disk' => 'products', 'ids' => ['17'], '--apply' => true]));
        $this->assertStringContainsString('0 promjena dozvola', Artisan::output());
    }

    #[DataProvider('invalidScopes')]
    public function test_unsafe_or_unbounded_scopes_are_rejected(string $disk, array $ids): void
    {
        $path = $this->image('category', '17', 'crest.jpg');
        $this->assertSame(1, Artisan::call('media:repair-permissions', ['disk' => $disk, 'ids' => $ids, '--apply' => true]));
        $this->assertSame(0700, $this->mode(dirname($path)));
        $this->assertSame(0600, $this->mode($path));
    }

    public static function invalidScopes(): array
    {
        return [
            ['local', ['17']], ['assets', ['17']], ['view', ['17']], ['public', ['17']],
            ['category', ['../17']], ['category', ['*']], ['category', ['17/nested']],
            ['category', ['0']], ['category', ['017']], ['category', range(1, 51)],
        ];
    }

    public function test_missing_id_causes_the_entire_plan_to_fail_before_applying_any_change(): void
    {
        $path = $this->image('category', '17', 'crest.jpg');
        $this->assertSame(1, Artisan::call('media:repair-permissions', ['disk' => 'category', 'ids' => ['17', '999'], '--apply' => true]));
        $this->assertSame(0700, $this->mode(dirname($path)));
        $this->assertSame(0600, $this->mode($path));
    }

    public function test_symbolic_id_directory_is_rejected_without_following_its_target(): void
    {
        $outside = $this->temporary . '/outside';
        mkdir($outside, 0700);
        file_put_contents($outside . '/private.jpg', 'must remain private');
        chmod($outside . '/private.jpg', 0600);
        symlink($outside, public_path('media/img/category/17'));
        $this->assertSame(1, Artisan::call('media:repair-permissions', ['disk' => 'category', 'ids' => ['17'], '--apply' => true]));
        $this->assertSame(0700, $this->mode($outside));
        $this->assertSame(0600, $this->mode($outside . '/private.jpg'));
    }

    public function test_hard_link_rejects_the_entire_plan_before_any_permissions_change(): void
    {
        $image = $this->image('category', '17', 'crest.jpg');
        $outside = $this->temporary . '/outside-private.jpg';
        file_put_contents($outside, 'private shared inode');
        chmod($outside, 0600);
        $this->assertTrue(link($outside, dirname($image) . '/linked.jpg'));
        $hash = hash_file('sha256', $outside);

        $this->assertSame(1, Artisan::call('media:repair-permissions', [
            'disk' => 'category', 'ids' => ['17'], '--apply' => true,
        ]));
        $this->assertStringContainsString('tvrde poveznice nisu dopuštene', Artisan::output());
        $this->assertSame(0700, $this->mode(dirname($image)));
        $this->assertSame(0600, $this->mode($image));
        $this->assertSame(0600, $this->mode($outside));
        $this->assertSame(0600, $this->mode(dirname($image) . '/linked.jpg'));
        $this->assertSame($hash, hash_file('sha256', $outside));
    }

    public function test_reconfigured_or_symbolic_media_root_is_rejected(): void
    {
        $path = $this->image('category', '17', 'crest.jpg');
        $expected = config('filesystems.disks.category.root');
        config(['filesystems.disks.category.root' => $this->temporary]);
        $this->assertSame(1, Artisan::call('media:repair-permissions', ['disk' => 'category', 'ids' => ['17'], '--apply' => true]));
        config(['filesystems.disks.category.root' => $expected]);
        rename($expected, $this->temporary . '/moved-category');
        symlink($this->temporary . '/moved-category', $expected);
        $this->assertSame(1, Artisan::call('media:repair-permissions', ['disk' => 'category', 'ids' => ['17'], '--apply' => true]));
        $this->assertSame(0600, $this->mode($this->temporary . '/moved-category/17/crest.jpg'));
    }

    private function image(string $disk, string $id, string $name): string
    {
        $directory = public_path('media/img/' . $disk . '/' . $id);
        if (!is_dir($directory)) {
            mkdir($directory, 0700);
        }
        // Permissions repair must leave bytes intact, independently of file decoding.
        $path = $directory . '/' . $name;
        file_put_contents($path, 'unchanged test image bytes');
        chmod($path, 0600);
        return $path;
    }

    private function mode(string $path): int
    {
        clearstatcache(true, $path);
        return fileperms($path) & 07777;
    }

    private function removeTree(string $path): void
    {
        if (is_link($path) || !is_dir($path)) {
            unlink($path);
            return;
        }
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $item) {
            $this->removeTree($item->getPathname());
        }
        rmdir($path);
    }
}
