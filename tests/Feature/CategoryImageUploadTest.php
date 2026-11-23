<?php

namespace Tests\Feature;

use App\Models\Back\Catalog\Category;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class CategoryImageUploadTest extends TestCase
{
    public function test_category_formats_keep_the_same_basename_across_slow_writes(): void
    {
        $disk = Storage::fake('category');
        $storedImage = null;
        $category = Mockery::mock(Category::class)->makePartial();
        $category->id = 140;
        $category->shouldReceive('translation')->andReturn((object) ['title' => 'GNK Šibenik']);
        $category->shouldReceive('update')->once()->andReturnUsing(function (array $values) use (&$storedImage) {
            $storedImage = $values['image'];
            return true;
        });

        $writes = 0;
        $proxy = Mockery::mock();
        $proxy->shouldReceive('put')->times(3)->andReturnUsing(function ($path, $bytes) use ($disk, &$writes) {
            $result = $disk->put($path, $bytes);
            if (++$writes === 1) {
                // Reproduce slow encoding/storage spanning a wall-clock second.
                usleep(1100000);
            }
            return $result;
        });
        Storage::shouldReceive('disk')->with('category')->andReturn($proxy);

        $upload = UploadedFile::fake()->image('GNK Šibenik grb.jpg', 600, 900);
        $request = Request::create('/category', 'POST', ['title' => ['hr' => 'GNK Šibenik']], [], ['image' => $upload]);
        $this->assertTrue((new Category)->validateRequest($request)->resolveImage($category));

        $relative = substr($storedImage, strlen(config('filesystems.disks.category.url')));
        $base = substr($relative, 0, -4);
        foreach ([$relative, $base . '.webp', $base . '-thumb.webp'] as $path) {
            $this->assertTrue($disk->exists($path), 'Missing matching category image: ' . $path);
        }
        $this->assertCount(3, $disk->allFiles());
        $dimensions = getimagesizefromstring($disk->get($base . '-thumb.webp'));
        $this->assertSame([400, 400], [$dimensions[0], $dimensions[1]]);
    }
}
