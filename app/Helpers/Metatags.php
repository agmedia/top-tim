<?php

namespace App\Helpers;


use App\Models\Front\Catalog\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class Metatags
{

    /**
     * @return string[]
     */
    public static function noFollow(): array
    {
        return [
            'name' => 'robots',
            'content' => 'noindex,nofollow'
        ];
    }


    /**
     * @return array
     */
    public static function indexSchema(): array
    {
        return [
            '@context' => 'https://schema.org/',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'logo' => asset('image/logo-top-tim.svg'),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => '+385 22 337000',
                'contactType' => 'Customer Service'
            ]
        ];
    }


    /**
     * @param Product|null    $prod
     * @param Collection|null $reviews
     *
     * @return array
     */
    public static function productSchema(Product $prod = null, Collection $reviews = null): array
    {
        $response = [];

        if ($prod) {
            $response = [
                '@context' => 'https://schema.org/',
                '@type' => 'Product',
                'name' => $prod->name,
                'description' => $prod->translation->meta_description,
                'image' => asset($prod->image),
                //'url' => url($prod->url),
                'offers' => [
                    '@type' => 'Offer',
                    'priceCurrency' => 'EUR',
                    'price' => $prod->special() ? static::formatPrice($prod->special()) : static::formatPrice($prod->price),
                    'sku' => $prod->sku,
                    'availability' => ($prod->quantity) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
                ],
            ];

            if ($reviews->count()) {
                $response['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => floor($reviews->avg('stars')),
                    'reviewCount' => $reviews->count(),
                ];

                foreach ($reviews as $review) {
                    $res_review = [
                        '@type' => 'Review',
                        'author' => $review->fname,
                        'datePublished' => Carbon::make($review->created_at)->locale('hr')->format('Y-m-d'),
                        'reviewBody' => strip_tags($review->message),
                        'name' => $prod->name,
                        'reviewRating' => [
                            '@type' => 'Rating',
                            'bestRating' => '5',
                            'ratingValue' => floor($review->stars),
                            'worstRating' => '1'
                        ]
                    ];
                }

                $response['review'] = $res_review;
            }
        }

        return $response;
    }


    /**
     * @param $price
     *
     * @return string
     */
    private static function formatPrice($price): string
    {
        return number_format($price, 2, '.', '');
    }
}
