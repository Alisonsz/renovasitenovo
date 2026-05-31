<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    private const SECTION_COPY = [
        'combos' => [
            'title' => 'Combos das áreas femininas mais procuradas',
            'subtitle' => 'Essa é sua chance de fazer as áreas mais desejadas com um superdesconto.',
            'button' => 'Ver combo',
        ],
        'pacotes' => [
            'title' => 'Pacotes femininos de depilação a laser',
            'subtitle' => 'São 10 sessões por área, com desconto especial comparado à contratação individual das sessões.',
            'button' => 'Ver pacote',
        ],
        'avulsas' => [
            'title' => 'Sessões avulsas femininas de depilação a laser',
            'subtitle' => 'Perfeitas para quem quer experimentar ou fazer retoques, sem compromisso com pacotes.',
            'button' => 'Ver oferta',
        ],
        'combos-depilacao-masculina' => [
            'title' => 'Combos masculinos mais procurados',
            'subtitle' => 'Mais áreas, mais desconto para cuidar da rotina com praticidade.',
            'button' => 'Ver combo',
        ],
        'pacotes-depilacao-masculina' => [
            'title' => 'Pacotes masculinos de depilação a laser',
            'subtitle' => 'Planos com 10 sessões por área e condições especiais.',
            'button' => 'Ver pacote',
        ],
        'sessoes-avulsas' => [
            'title' => 'Sessões avulsas masculinas de depilação a laser',
            'subtitle' => 'Opções pontuais para experimentar ou manter o resultado.',
            'button' => 'Ver oferta',
        ],
    ];

    public function show(Request $request, ?string $slug = null): Response
    {
        $slug ??= (string) $request->route('slug');

        $category = ProductCategory::query()
            ->with('children')
            ->where('slug', $slug)
            ->firstOrFail();

        $sectionCategories = $category->children->isNotEmpty()
            ? $category->children
            : collect([$category]);

        $sections = $sectionCategories
            ->map(fn (ProductCategory $sectionCategory) => $this->sectionPayload($sectionCategory))
            ->values();

        return Inertia::render('Store/Category', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
            ],
            'children' => $category->children->map(fn ($child) => [
                'id' => $child->id,
                'name' => $child->name,
                'slug' => $child->slug,
            ])->values(),
            'sections' => $sections,
        ]);
    }

    private function sectionPayload(ProductCategory $category): array
    {
        $copy = self::SECTION_COPY[$category->slug] ?? [
            'title' => $category->name,
            'subtitle' => $category->description,
            'button' => 'Ver oferta',
        ];

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'title' => $copy['title'],
            'subtitle' => $copy['subtitle'],
            'button_label' => $copy['button'],
            'products' => $this->productsForSection($category),
        ];
    }

    private function productsForSection(ProductCategory $category)
    {
        $query = $category->products()
            ->with(['images', 'categories'])
            ->where('is_active', true)
            ->where(function ($query) use ($category) {
                $query
                    ->where('primary_category_id', $category->id)
                    ->orWhereHas('categories', fn ($categoryQuery) => $categoryQuery->where('product_categories.id', $category->id));
            })
            ->orderByRaw('CASE WHEN price_cents = 0 THEN 1 ELSE 0 END');

        if ($category->slug === 'combos') {
            $query
                ->orderByRaw('CASE WHEN wp_product_id = 5972 THEN 0 ELSE 1 END')
                ->orderByDesc('wp_product_id');
        } else {
            $query->orderByDesc('wp_product_id');
        }

        return $query->orderBy('name')
            ->get()
            ->unique('id')
            ->values()
            ->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => strip_tags((string) $product->short_description),
                'regular_price_cents' => $product->regular_price_cents,
                'sale_price_cents' => $product->sale_price_cents,
                'price_cents' => $product->price_cents,
                'image_url' => $product->images->first()?->local_path ?: $product->images->first()?->url,
                'categories' => $product->categories->map(fn ($item) => [
                    'name' => $item->name,
                    'slug' => $item->slug,
                ])->values(),
            ]);
    }
}
