<?php

namespace App\Helpers;

use App\Models\Back\Catalog\Brand;
use App\Models\Back\Catalog\Category;
use App\Models\Back\Catalog\CategoryTranslation;
use App\Models\Back\Catalog\Product\ProductImage;
use App\Models\Back\Catalog\Product\ProductImageTranslation;
use App\Models\Back\Catalog\Publisher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class Import
{

    /**
     * @param string $image
     * @param string $name
     * @param int    $id
     *
     * @return string
     */
    public function resolveImages(string $image, string $name, int $id): string
    {
        $response = '';

        if ($image) {
            $time = time() . Str::random(9);

            $image_saved = Storage::disk('local')->put('temp/' . $id . '.jpg', file_get_contents($image));

            if ($image_saved) {
                try {
                    $image = Storage::disk('local')->get('temp/' . $id . '.jpg');
                    $img = Image::make($image);
                } catch (\Exception $e) {
                    //Log::info('Error downloading image: ' . $image);
                    //Log::info($e->getMessage());
                }

                $str = $id . '/' . Str::limit(Str::slug($name)) . '-' . $time . '.';

                $path = $str . 'jpg';
                Storage::disk('products')->put($path, $img->encode('jpg'));

                $path_webp = $str . 'webp';
                Storage::disk('products')->put($path_webp, $img->encode('webp'));

                // Thumb creation
                $str_thumb = $id . '/' . Str::limit(Str::slug($name)) . '-' . $time . '-thumb.';
                $canvas = Image::canvas(400, 400, '#ffffff');

                $img = $img->resize(null, 400, function ($constraint) {
                    $constraint->aspectRatio();
                });

                $canvas->insert($img, 'center');

                $path_webp_thumb = $str_thumb . 'webp';
                Storage::disk('products')->put($path_webp_thumb, $canvas->encode('webp'));

                $response = config('filesystems.disks.products.url') . $path;

                Storage::disk('local')->delete('temp/' . $id . '.jpg');
            }
        }

        return $response;
    }


    /**
     * @param        $product_id
     * @param string $path
     * @param string $name
     * @param        $default
     * @param        $sort_order
     *
     * @return bool
     */
    public function saveImageToDB($product_id, string $path, string $name, $default = 0, $sort_order = 0): bool
    {
        $new_image_id = ProductImage::query()->insertGetId([
            'product_id' => $product_id,
            'image'      => $path,
            'default'    => $default,
            'published'  => 1,
            'sort_order' => $sort_order,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);

        if ($new_image_id) {
            foreach (ag_lang() as $lang) {
                ProductImageTranslation::query()->insertGetId([
                    'product_image_id' => $new_image_id,
                    'lang'             => $lang->code,
                    'title'            => $name,
                    'alt'              => $name,
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now()
                ]);
            }

            return true;
        }

        return false;
    }


    /**
     * @param array $categories
     *
     * @return int|mixed
     */
    public function resolveCategories(array $categories)
    {
        $parent = config('settings.eng_default_category');
        $response[] = $parent;

        foreach ($categories as $category) {
            $parent_id = 0;

            if (isset($category['parentId']['title'])) {
                $parent_id = $this->saveCategory($category['parentId']['title'], $parent, $category['parentId']['position']);
                $response[] = $parent_id;
            }

            if ( ! $parent_id) {
                $response[] = $this->saveCategory($category['title'], $parent, $category['position']);
            }

        }

        return $response;
    }


    /**
     * @param array $categories
     *
     * @return int|mixed
     */
    public function resolveStringCategories(string $categories)
    {
        $categories = explode('\\', $categories);

        if (isset($categories[0])) {
            $top_id = $this->saveCategory($categories[0]);
            $response[] = $top_id;
        }

        if (isset($categories[1])) {
            $sub_id = $this->saveCategory($categories[1], $top_id);
            $response[] = $sub_id;
        }

        return $response;
    }


    /**
     * @param string $name
     * @param int    $parent
     *
     * @return mixed
     */
    private function saveCategory(string $name, int $parent = 0, int $sort_order = 0)
    {
        $exist = CategoryTranslation::query()->where('title', $name)->first();

        if ( ! $exist) {
            $main_id = Category::insertGetId([
                'parent_id'  => $parent,
                'group'      => 'kategorija-proizvoda',
                'sort_order' => $sort_order,
                'status'     => 1,
                'updated_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            CategoryTranslation::insertGetId([
                'category_id'      => $main_id,
                'lang'             => 'hr',
                'title'            => $name,
                'description'      => '',
                'meta_title'       => $name,
                'meta_description' => '',
                'slug'             => Str::slug($name),
                'created_at'       => Carbon::now(),
                'updated_at'       => Carbon::now()
            ]);

            return $main_id;
        }

        return $exist->category_id;
    }


    /**
     * @param string $author
     *
     * @return int
     */
    public function resolveAuthor(string $author = null): int
    {
        if ($author) {
            $author = trim($author);

            $exist = Brand::where('title', $author)->first();

            if ( ! $exist) {
                return Brand::insertGetId([
                    'letter'           => Helper::resolveFirstLetter($author),
                    'title'            => $author,
                    'description'      => '',
                    'meta_title'       => $author,
                    'meta_description' => '',
                    'lang'             => 'hr',
                    'sort_order'       => 0,
                    'status'           => 1,
                    'slug'             => Str::slug($author),
                    'url'              => config('settings.author_path') . '/' . Str::slug($author),
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now()
                ]);
            }

            return $exist->id;
        }

        return config('settings.unknown_author');
    }


    /**
     * @param string      $start_tag
     * @param string|null $end_tag
     *
     * @return string
     */
    public function resolveContent(string $content, string $start_tag, string $end_tag = null): string
    {
        $content = strip_tags($content);
        $ini = strlen($start_tag);
        $len = strpos($content, $end_tag, $ini) - $ini;

        return trim(substr($content, $ini, $len));
    }


    /**
     * @param string $publisher
     *
     * @return int
     */
    public function resolvePublisher(string $publisher = null): int
    {
        if ($publisher) {

            //Log::info('$publisher..... ' . $publisher);

            $exist = Publisher::where('title', $publisher)->first();

            if ( ! $exist) {
                return Publisher::insertGetId([
                    'letter'           => Helper::resolveFirstLetter($publisher),
                    'title'            => $publisher,
                    'description'      => '',
                    'meta_title'       => $publisher,
                    'meta_description' => '',
                    'lang'             => 'hr',
                    'sort_order'       => 0,
                    'status'           => 1,
                    'slug'             => Str::slug($publisher),
                    'url'              => config('settings.publisher_path') . '/' . Str::slug($publisher),
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now()
                ]);
            }

            return $exist->id;
        }

        return config('settings.unknown_publisher');
    }


    public function getFromURL(string $url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }


    /**
     * @param string $text
     *
     * @return string
     */
    private function replaceNames(string $text): string
    {
        $text = str_replace('Knji?evnost', 'Književnost', $text);
        $text = str_replace('Kazali?te', 'Kazalište', $text);
        $text = str_replace('knji?evnosti', 'književnosti', $text);

        return $text;
    }
}
