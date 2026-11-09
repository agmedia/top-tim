<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Facades\Image;

class SafeMediaImage
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_PIXELS = 16000000;
    private const MAX_SIDE = 8000;
    private const MAX_OUTPUT_SIDE = 1600;

    /** Map approved media URLs to local files; this method never opens a URL. */
    public function localPath(string $source): ?string
    {
        if ($source === '' || strlen($source) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $source)) {
            return null;
        }

        $parts = parse_url($source);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
                return null;
            }

            $hosts = [];
            foreach ([config('app.url'), config('settings.images_domain')] as $origin) {
                $host = is_string($origin) ? parse_url($origin, PHP_URL_HOST) : null;
                if (is_string($host)) {
                    $hosts[] = strtolower($host);
                }
            }
            if (! in_array(strtolower($parts['host'] ?? ''), $hosts, true)
                || (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true))) {
                return null;
            }
        }

        $path = rawurldecode($parts['path'] ?? '');
        if (preg_match('/[\x00-\x20\x7f\\\\%]/', $path)
            || preg_match('#(?:^|/)\.{1,2}(?:/|$)#', $path)) {
            return null;
        }
        $path = ltrim($path, '/');
        if (strpos($path, 'media/') !== 0) {
            return null;
        }

        $root = realpath(public_path('media'));
        $resolved = realpath(public_path($path));
        if (! $root || ! $resolved || ! is_file($resolved)
            || strpos($resolved, $root . DIRECTORY_SEPARATOR) !== 0
            || ! preg_match('/\.(?:jpe?g|png|gif|webp)$/iD', $resolved)) {
            return null;
        }

        return $resolved;
    }

    public function response(Request $request, bool $thumbnail = false)
    {
        $request->validate([
            'src' => ['sometimes', 'required', 'string', 'max:2048'],
            'size' => ['sometimes', 'required', 'string', 'regex:/^[1-9][0-9]{0,3}(?:x[1-9][0-9]{0,3})?$/D'],
        ]);
        $path = $this->localPath($request->input('src', config('settings.image_default')));
        abort_unless($path, 404);

        $width = $height = $thumbnail ? 400 : self::MAX_OUTPUT_SIDE;
        if ($thumbnail && $request->has('size')) {
            $dimensions = explode('x', $request->input('size'));
            $width = (int) $dimensions[0];
            $height = (int) ($dimensions[1] ?? $dimensions[0]);
        }
        abort_if($width > self::MAX_OUTPUT_SIDE || $height > self::MAX_OUTPUT_SIDE, 422);
        $this->assertRaster($path, 'src');

        $key = 'safe-media-image:v1:' . hash('sha256', json_encode([
            $path, filemtime($path), filesize($path), $width, $height,
        ]));
        $bytes = Cache::remember($key, now()->addMinutes(60), function () use ($path, $width, $height) {
            return $this->encode($path, $width, $height, 'src');
        });

        return response($bytes, 200, [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function upload(Request $request, string $disk, string $folderField): array
    {
        $validated = $request->validate([
            'upload' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            $folderField => ['nullable', 'integer', 'min:1'],
        ]);
        $file = $request->file('upload');
        $this->assertRaster($file->getPathname(), 'upload');
        $bytes = $this->encode($file->getPathname(), self::MAX_OUTPUT_SIDE, self::MAX_OUTPUT_SIDE, 'upload');
        $name = Str::random(32) . '.png';
        $folder = isset($validated[$folderField]) ? (string) (int) $validated[$folderField] . '/' : '';
        $path = $folder . $name;
        abort_unless(Storage::disk($disk)->put($path, $bytes), 500);

        return ['fileName' => $name, 'uploaded' => true,
            'url' => url(config('filesystems.disks.' . $disk . '.url') . $path)];
    }

    private function assertRaster(string $path, string $field): void
    {
        $size = @filesize($path);
        $dimensions = $size !== false && $size <= self::MAX_BYTES ? @getimagesize($path) : false;
        if (! $dimensions || $dimensions[0] < 1 || $dimensions[1] < 1
            || $dimensions[0] > self::MAX_SIDE || $dimensions[1] > self::MAX_SIDE
            || $dimensions[0] * $dimensions[1] > self::MAX_PIXELS
            || ! in_array($dimensions['mime'] ?? '', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            throw ValidationException::withMessages([$field => 'Odaberite valjanu sliku do 5 MB i 16 megapiksela.']);
        }
    }

    private function encode(string $path, int $width, int $height, string $field): string
    {
        try {
            $image = Image::make($path)->orientate()->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $bytes = (string) $image->encode('png');
            $image->destroy();
            return $bytes;
        } catch (\Intervention\Image\Exception\NotReadableException | \Intervention\Image\Exception\NotSupportedException $exception) {
            throw ValidationException::withMessages([$field => 'Sliku nije moguće obraditi.']);
        }
    }
}
