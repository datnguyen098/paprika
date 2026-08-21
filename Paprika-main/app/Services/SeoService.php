<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Collection;

class SeoService
{
    public const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80';

    public static function defaultImage(): string
    {
        return media_url(setting('og_image'), self::DEFAULT_IMAGE);
    }

    public static function page(
        ?string $title,
        ?string $description,
        ?string $keywords,
        ?string $canonical = null,
        ?string $image = null,
        string $type = 'website'
    ): array {
        return [
            'title' => $title ?: setting('default_meta_title', 'Paprika Patras | Vietnamese Cuisine'),
            'description' => $description ?: setting('default_meta_description', 'Order pho, banh mi, nem, rolls, and grilled Vietnamese dishes from Paprika in Patras.'),
            'keywords' => $keywords ?: setting('default_meta_keywords', 'Paprika Patras, Vietnamese cuisine Patras, pho Patras, banh mi Patras'),
            'canonical' => $canonical ?: url()->current(),
            'image' => media_url($image, self::defaultImage()),
            'type' => $type,
        ];
    }

    public static function restaurantSchema(): array
    {
        $branch = primary_branch();
        $phone = $branch?->hotline ?: $branch?->phone ?: setting('schema_phone', setting('hotline', '+30 694 041 4566'));
        $email = $branch?->email ?: setting('email', 'hello@paprika-patras.gr');
        $address = $branch?->address ?: setting('schema_address', setting('address', 'Patras, Greece'));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Restaurant',
            'name' => setting('schema_restaurant_name', setting('restaurant_name', 'Paprika')),
            'image' => self::defaultImage(),
            'url' => localized_route('home'),
            'telephone' => $phone,
            'email' => $email,
            'priceRange' => setting('schema_price_range', '€€'),
            'servesCuisine' => ['Vietnamese', 'Greek', 'Grill'],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => 'Patras',
                'addressRegion' => 'Western Greece',
                'addressCountry' => 'GR',
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '12:00',
                    'closes' => '23:00',
                ],
            ],
            'sameAs' => array_values(array_filter([
                $branch?->facebook_url ?: setting('facebook_url'),
                $branch?->zalo_url ?: setting('zalo_url'),
                setting('instagram_url'),
                setting('youtube_url'),
                setting('tiktok_url'),
            ])),
        ];
    }

    public static function menuSchema(Collection $categories): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Menu',
            'name' => 'Paprika Menu',
            'url' => localized_route('menu.index'),
            'hasMenuSection' => $categories->map(function (Category $category): array {
                return [
                    '@type' => 'MenuSection',
                    'name' => $category->localized('name'),
                    'description' => $category->localized('description'),
                    'hasMenuItem' => $category->dishes->map(function ($dish): array {
                        $item = [
                            '@type' => 'MenuItem',
                            'name' => $dish->localized('name'),
                            'description' => $dish->localized('description'),
                            'image' => media_url($dish->image, self::defaultImage()),
                            'url' => localized_route('menu.show', ['slug' => $dish->localizedSlug()]),
                        ];

                        if (show_dish_prices()) {
                            $item['offers'] = [
                                '@type' => 'Offer',
                                'price' => number_format(((int) ($dish->sale_price ?: $dish->price)) / 100, 2, '.', ''),
                                'priceCurrency' => 'EUR',
                            ];
                        }

                        return $item;
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    public static function dishSchema($dish): array
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'MenuItem',
            'name' => $dish->localized('name'),
            'description' => $dish->localized('meta_description') ?: $dish->localized('description'),
            'image' => media_url($dish->image, self::defaultImage()),
            'url' => localized_route('menu.show', ['slug' => $dish->localizedSlug()]),
            'menuAddOn' => $dish->category?->localized('name'),
        ];

        if (show_dish_prices()) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => number_format(((int) ($dish->sale_price ?: $dish->price)) / 100, 2, '.', ''),
                'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
            ];
        }

        return $schema;
    }

    public static function articleSchema(Post $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post->localized('title'),
            'description' => $post->localized('meta_description') ?: $post->localized('excerpt'),
            'image' => media_url($post->thumbnail, self::DEFAULT_IMAGE),
            'datePublished' => optional($post->published_at)->toAtomString(),
            'dateModified' => $post->updated_at->toAtomString(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Paprika',
                'url' => localized_route('home'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Paprika',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => self::defaultImage(),
                ],
            ],
            'mainEntityOfPage' => localized_route('blog.show', ['slug' => $post->localizedSlug()]),
        ];
    }

    /**
     * @param array<int, array{question: string, answer: string}> $faqs
     */
    public static function faqSchema(array $faqs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)
                ->map(fn (array $faq): array => [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    public static function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'] ?? url()->current(),
                ])
                ->all(),
        ];
    }
}
