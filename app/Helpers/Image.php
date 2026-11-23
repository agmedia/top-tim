<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Image
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_PIXELS = 16000000;
    private const MAX_SIDE = 8000;

    /**
     * @param string $base_64_string
     *
     * @return false|string
     */
    public static function makeImageFromBase(string $base_64_string): string
    {
        $comma = strpos($base_64_string, ',');
        $types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($comma === false || $comma > 32
            || !in_array(substr($base_64_string, 0, $comma), array_map(fn ($mime) => 'data:' . $mime . ';base64', $types), true)
            || strlen($base_64_string) - $comma - 1 > 4 * (int) ceil(self::MAX_BYTES / 3)) {
            throw self::invalidImage();
        }

        $bytes = base64_decode(substr($base_64_string, $comma + 1), true);
        $dimensions = $bytes !== false && strlen($bytes) <= self::MAX_BYTES ? @getimagesizefromstring($bytes) : false;
        if (!$dimensions || $dimensions[0] < 1 || $dimensions[1] < 1
            || $dimensions[0] > self::MAX_SIDE || $dimensions[1] > self::MAX_SIDE
            || $dimensions[0] * $dimensions[1] > self::MAX_PIXELS
            || !in_array($dimensions['mime'] ?? '', $types, true)) {
            throw self::invalidImage();
        }

        return $bytes;
    }

    private static function invalidImage(): ValidationException
    {
        return ValidationException::withMessages(['image' => 'Odaberite valjanu JPG, PNG, GIF ili WebP fotografiju do 5 MB i 16 megapiksela.']);
    }


    /**
     * @param string $folder
     * @param string $path
     *
     * @return string
     */
    public static function cleanPath(string $disk, string $folder, string $path): string
    {
        return str_replace(config('filesystems.disks.' . $disk . '.url') . $folder . '/', '', $path);
    }


    /**
     * @param        $image
     * @param string $target
     *
     * @return int
     */
    public static function setPreferedWidth($image, string $target = 'image'): array
    {
        $ratio = explode('x', config('settings.' . $target . '_size_ratio'));

        $width  = $ratio[0];
        $height = $ratio[1];

        if ($image->getWidth() < $image->getHeight()) {
            $width  = $ratio[1];
            $height = $ratio[0];
        }

        return [
            'width'  => $width,
            'height' => $height
        ];
    }


    /**
     * @param string $disk
     * @param string $new_image
     * @param        $resource
     *
     * @return string
     */
    public static function save(string $disk, array $new_image, $resource): string
    {
        $time  = Str::random(16);
        $image = is_string($new_image['image'] ?? null) ? json_decode($new_image['image']) : null;
        if (!is_string($image->output->image ?? null)) {
            throw self::invalidImage();
        }
        $bytes = self::makeImageFromBase($image->output->image);
        try {
            $img = \Intervention\Image\Facades\Image::make($bytes)->orientate();
        } catch (\Intervention\Image\Exception\NotReadableException | \Intervention\Image\Exception\NotSupportedException $exception) {
            throw self::invalidImage();
        }

        // Image creation
        $img_ratio = static::setPreferedWidth($img);
        $path      = $resource->id . '/' . Str::slug($resource->translation->name) . '-' . $time . '.';

        $img = $img->resize($img_ratio['width'], null, function ($constraint) {
            $constraint->aspectRatio();
        })->resizeCanvas('50', '50', 'center', true);

        $path_jpg  = $path . 'jpg';
        $path_webp = $path . 'webp';

        $files = [
            $path_jpg => (string) $img->encode('jpg'),
            $path_webp => (string) $img->encode('webp'),
        ];

        // Thumb creation
        $thumb_ratio = static::setPreferedWidth($img, 'thumb');
        $path_thumb  = $resource->id . '/' . Str::slug($resource->translation->name) . '-' . $time . '-thumb.';

        $img = $img->resize($thumb_ratio['width'], null, function ($constraint) {
            $constraint->aspectRatio();
        })->resizeCanvas('10', '10', 'center', true);

        $path_webp_thumb = $path_thumb . 'webp';
        $files[$path_webp_thumb] = (string) $img->encode('webp');
        $img->destroy();
        $storage = Storage::disk($disk);
        try {
            foreach ($files as $filename => $contents) {
                if (!$storage->put($filename, $contents)) {
                    throw new \RuntimeException('Sliku nije moguće spremiti. Pokušajte ponovno.');
                }
            }
        } catch (\Throwable $exception) {
            // These new random filenames can be removed without touching the old image.
            try { $storage->delete(array_keys($files)); } catch (\Throwable $cleanupException) {}
            throw $exception;
        }

        return $path_jpg;
    }


    /**
     * @param string $disk
     * @param string $folder
     * @param string $path
     */
    public static function delete(string $disk, string $folder, string $path): void
    {
        $webp  = str_replace('.jpg', '.webp', $path);
        $thumb = str_replace('.jpg', '-thumb.webp', $path);

        Storage::disk($disk)->delete($folder . '/' . $path);
        Storage::disk($disk)->delete($folder . '/' . $webp);
        Storage::disk($disk)->delete($folder . '/' . $thumb);
    }


    /**
     * @param string $src
     *
     * @return string|bool
     */
    public static function checkIsValidImage(string $src): string|bool
    {
        return app(\App\Services\SafeMediaImage::class)->localPath($src) ?: false;
    }

}
