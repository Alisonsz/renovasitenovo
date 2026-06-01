<?php

namespace App\Services\Merchant;

use App\Models\Product;
use DOMDocument;

class GoogleMerchantFeedService
{
    public function xml(): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $rss->setAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $dom->appendChild($rss);

        $channel = $dom->createElement('channel');
        $rss->appendChild($channel);
        $channel->appendChild($dom->createElement('title', 'Renova Laser Depilação'));
        $channel->appendChild($dom->createElement('link', url('/')));
        $channel->appendChild($dom->createElement('description', 'Catálogo de serviços Renova Laser'));

        Product::query()
            ->with('images')
            ->where('is_active', true)
            ->where('merchant_visibility', 'sync-and-show')
            ->where('price_cents', '>', 0)
            ->orderBy('id')
            ->get()
            ->each(function (Product $product) use ($dom, $channel): void {
                $item = $dom->createElement('item');
                $channel->appendChild($item);

                $this->append($dom, $item, 'g:id', $product->merchant_google_id ?: (string) $product->id);
                $this->append($dom, $item, 'g:title', $product->name);
                $this->append($dom, $item, 'g:description', strip_tags((string) ($product->short_description ?: $product->description ?: $product->name)));
                $this->append($dom, $item, 'g:link', route('store.product', ['product' => $product->slug]));

                $image = $product->images->first();
                if ($image) {
                    $this->append($dom, $item, 'g:image_link', $image->local_path ?: $image->url);
                }

                $this->append($dom, $item, 'g:availability', $product->stock_status === 'instock' ? 'in_stock' : 'out_of_stock');
                $this->append($dom, $item, 'g:price', $this->money($product->regular_price_cents ?: $product->price_cents, $product->currency));

                if ($product->sale_price_cents && $product->sale_price_cents < $product->regular_price_cents) {
                    $this->append($dom, $item, 'g:sale_price', $this->money($product->sale_price_cents, $product->currency));
                }

                $this->append($dom, $item, 'g:brand', $product->merchant_brand ?: 'Renova Laser Depilação');
                $this->append($dom, $item, 'g:condition', $product->merchant_condition ?: 'new');
                $this->append($dom, $item, 'g:age_group', $product->merchant_age_group ?: 'adult');
                $this->append($dom, $item, 'g:gender', $product->merchant_gender ?: 'unisex');
                $this->append($dom, $item, 'g:color', $product->merchant_color ?: 'Padrão');
                $this->append($dom, $item, 'g:size', $product->merchant_size ?: 'Padrão');
                $this->append($dom, $item, 'g:is_bundle', $product->merchant_is_bundle ? 'yes' : 'no');
                $this->append($dom, $item, 'g:adult', 'no');
            });

        return (string) $dom->saveXML();
    }

    private function append(DOMDocument $dom, \DOMElement $parent, string $name, string $value): void
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($value));
        $parent->appendChild($element);
    }

    private function money(int $cents, string $currency): string
    {
        return number_format($cents / 100, 2, '.', '').' '.$currency;
    }
}
