<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Front\Catalog\Product;
use Illuminate\Support\Str;
use XMLWriter;

class ProductFeedController extends Controller
{
    public function google()
    {
        return response()->stream(function () {
            $xml = new XMLWriter();
            $xml->openUri('php://output');
            $xml->startDocument('1.0', 'UTF-8');
            $xml->startElement('rss');
            $xml->writeAttribute('version', '2.0');
            $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
            $xml->startElement('channel');
            $xml->writeElement('title', config('app.name'));
            $xml->writeElement('link', config('app.url'));
            $xml->writeElement('description', 'Katalog proizvoda');

            foreach ($this->products() as $product) {
                $xml->startElement('item');
                foreach ($product as $field => $value) {
                    if ($value !== '') {
                        $xml->writeElement('g:' . $field, $value);
                    }
                }
                $xml->endElement();
            }

            $xml->endDocument();
            $xml->flush();
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function meta()
    {
        return response()->stream(function () {
            $output = fopen('php://output', 'w');
            $fields = ['id', 'title', 'description', 'availability', 'condition', 'price', 'link', 'image_link', 'brand', 'sale_price'];
            fputcsv($output, $fields, ',', '"', '');

            foreach ($this->products() as $product) {
                $product['availability'] = str_replace('_', ' ', $product['availability']);
                fputcsv($output, array_map(function ($field) use ($product) {
                    return $product[$field];
                }, $fields), ',', '"', '');
            }

            fclose($output);
        }, 200, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function products(): iterable
    {
        // Keep language and pricing independent of the visitor's session.
        \Mcamara\LaravelLocalization\Facades\LaravelLocalization::setLocale('hr');
        session()->put('locale', 'hr');

        foreach (Product::active()->where('price', '>', 0)->whereHas('translation')
                     ->with(['translation', 'brand.translation'])->lazyById(200) as $product) {
            if (!$product->getRawOriginal('image') || !$product->category()) {
                continue;
            }

            $price = (float) $product->main_price;
            $special = (float) $product->main_special;
            $title = $this->plainText($product->name, 150);
            if ($title === '' || $price <= 0) {
                continue;
            }

            yield [
                'id' => (string) $product->id,
                'title' => $title,
                'description' => $this->plainText($product->description, 5000) ?: $title,
                'availability' => $product->quantity > 0 ? 'in_stock' : 'out_of_stock',
                'condition' => 'new',
                'price' => $this->price($price),
                'link' => rtrim(config('app.url'), '/') . '/' . ltrim($product->url, '/'),
                'image_link' => url($product->image),
                'brand' => $this->plainText(optional(optional($product->brand)->translation)->title, 70),
                'sale_price' => $special > 0 && $special < $price ? $this->price($special) : '',
            ];
        }
    }

    private function plainText(?string $text, int $limit): string
    {
        $text = html_entity_decode(strip_tags($text ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text);

        return Str::limit(trim($text), $limit, '');
    }

    private function price(float $price): string
    {
        return number_format($price, 2, '.', '') . ' EUR';
    }
}
