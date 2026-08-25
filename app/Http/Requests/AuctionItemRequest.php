<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\AuctionItemService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuctionItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('products', 'id'),
                $this->productIsFree(...),
            ],
            'starting_price' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
        ];
    }

    /**
     * A product may sit in only one auction that has not ended, and a lot that
     * sold never comes back.
     */
    private function productIsFree(string $attribute, mixed $value, Closure $fail): void
    {
        $product = Product::find($value);

        if (! $product instanceof Product) {
            return;
        }

        if (! app(AuctionItemService::class)->isProductAvailable($product)) {
            $fail('This product was sold in a previous auction, or is already in one that has not ended.');
        }
    }
}
