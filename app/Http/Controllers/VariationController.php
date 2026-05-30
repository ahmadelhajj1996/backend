<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Jobs\RecalculateVariationPriceJob;
use App\Models\Characteristic;
use App\Models\Product;
use App\Models\Variation;
use App\Models\VariationAttribute;
use App\Models\VariationImage;
use App\Services\VariationRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VariationController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = Variation::with([
                'product:id,name',
                'images:id,variation_id,path',
                'attributes:id,variation_id,attribute_option_id',
                'attributes.option:id,attribute_id,value,color_code',
                'attributes.option.attribute:id,name',
                'characteristics:id,variation_id,attribute',
            ]);

            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('is_active')) {
                $query->where(
                    'is_active',
                    filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN)
                );
            }

            return $this->successResponse(
                $query->latest()->paginate($request->get('per_page', 15)),
                'Variations retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {

            $variation = Variation::with([
                'product',
                'images',
                'attributes.option.attribute',
                'characteristics',
            ])->find($id);

            if (! $variation) {
                return $this->notFoundResponse('Variation not found');
            }

            return $this->successResponse($variation);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {

            $variation = Variation::with([
                'images',
                'attributes',
                'characteristics',
            ])->find($id);

            if (! $variation) {
                return $this->notFoundResponse('Variation not found');
            }

            DB::transaction(function () use ($variation) {

                foreach ($variation->images as $image) {
                    ImageHelper::delete($image->path);
                }

                $variation->images()->delete();
                $variation->attributes()->delete();
                $variation->characteristics()->delete();
                $variation->delete();
            });

            return $this->deletedResponse('Variation deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getByProduct($productId)
    {
        try {

            $product = Product::find($productId);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            $variations = Variation::with([
                'images',
                'attributes.option.attribute',
                'characteristics',
            ])
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->orderBy('is_default', 'desc')
                ->orderBy('sell_price', 'asc')
                ->get();

            return $this->successResponse($variations);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        try {

            $validated = $this->validateVariation($request);

            $variation = DB::transaction(function () use ($validated, $request) {

                $mainImage = $request->hasFile('image')
                    ? ImageHelper::upload($request->file('image'), 'variations')
                    : null;

                $rates = VariationRateService::snapshot();

                $variation = Variation::create([
                    'product_id'     => $validated['product_id'],
                    'sku'            => $validated['sku'],

                    'base_price'     => $validated['base_price'],
                    'base_buy_price' => $validated['base_buy_price'] ?? 0,

                    'sell_rate'      => $rates['sell_rate'],
                    'buy_rate'       => $rates['buy_rate'],

                    'sell_price'     => round($validated['base_price'] * $rates['sell_rate'], 2),
                    'buy_price'      => round(($validated['base_buy_price'] ?? 0) * $rates['buy_rate'], 2),

                    'quantity'       => $validated['quantity'] ?? 0,
                    'sold_count'     => $validated['sold_count'] ?? 0,

                    'is_default'     => $validated['is_default'] ?? false,
                    'is_active'      => $validated['is_active'] ?? true,
                    'image'          => $mainImage,
                ]);

                foreach ($validated['attributes'] ?? [] as $attribute) {

                    if (
                        empty($attribute['attribute_id']) ||
                        empty($attribute['attribute_option_id'])
                    ) {
                        continue;
                    }

                    VariationAttribute::create([
                        'variation_id'        => $variation->id,
                        'attribute_id'        => $attribute['attribute_id'],
                        'attribute_option_id' => $attribute['attribute_option_id'],
                        'price_override'      => $attribute['price_override'] ?? null,
                        'is_price_override'   => ! empty($attribute['price_override']),
                    ]);
                }

                foreach ($validated['characteristics'] ?? [] as $characteristic) {
                    Characteristic::create([
                        'variation_id' => $variation->id,
                        'attribute'    => $characteristic['attribute'],
                    ]);
                }

                foreach ($request->file('images', []) as $image) {
                    VariationImage::create([
                        'variation_id' => $variation->id,
                        'path'         => ImageHelper::upload($image, 'variations'),
                    ]);
                }

                return $variation;
            });

            RecalculateVariationPriceJob::dispatch($variation->id);

            return $this->createdResponse(
                $variation->load([
                    'product',
                    'images',
                    'attributes.option.attribute',
                    'characteristics',
                ])
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        try {

            $variation = Variation::findOrFail($id);
            $validated = $this->validateVariation($request, $variation->id);

            $updated = DB::transaction(function () use ($variation, $validated, $request) {

                $rates = VariationRateService::snapshot();

                $variation->update([
                    'product_id'     => $validated['product_id'],
                    'sku'            => $validated['sku'],

                    'base_price'     => $validated['base_price'],
                    'base_buy_price' => $validated['base_buy_price'] ?? 0,

                    'sell_rate'      => $rates['sell_rate'],
                    'buy_rate'       => $rates['buy_rate'],

                    'sell_price'     => round($validated['base_price'] * $rates['sell_rate'], 2),
                    'buy_price'      => round(($validated['base_buy_price'] ?? 0) * $rates['buy_rate'], 2),

                    'quantity'       => $validated['quantity'] ?? 0,
                    'sold_count'     => $validated['sold_count'] ?? 0,
                    'is_default'     => $validated['is_default'] ?? false,
                    'is_active'      => $validated['is_active'] ?? true,
                ]);

                $variation->attributes()->delete();

                foreach ($validated['attributes'] ?? [] as $attribute) {

                    if (
                        empty($attribute['attribute_id']) ||
                        empty($attribute['attribute_option_id'])
                    ) {
                        continue;
                    }

                    VariationAttribute::create([
                        'variation_id'        => $variation->id,
                        'attribute_id'        => $attribute['attribute_id'],
                        'attribute_option_id' => $attribute['attribute_option_id'],
                        'price_override'      => $attribute['price_override'] ?? null,
                        'is_price_override'   => ! empty($attribute['price_override']),
                    ]);
                }

                $variation->characteristics()->delete();

                foreach ($validated['characteristics'] ?? [] as $characteristic) {
                    Characteristic::create([
                        'variation_id' => $variation->id,
                        'attribute'    => $characteristic['attribute'],
                    ]);
                }

                return $variation->fresh();
            });

            RecalculateVariationPriceJob::dispatch($updated->id);

            return $this->updatedResponse($updated);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */
    private function validateVariation(Request $request, $ignoreId = null)
    {
        $rule = Rule::unique('variations', 'sku');

        if ($ignoreId) {
            $rule->ignore($ignoreId);
        }

        return $request->validate([
            'product_id'                       => ['required', 'exists:products,id'],
            'sku'                              => ['required', 'string', 'max:100', $rule],
            'base_price'                       => ['required', 'numeric', 'min:0'],
            'base_buy_price'                   => ['required', 'numeric', 'min:0'],
            'quantity'                         => ['nullable', 'integer', 'min:0'],
            'sold_count'                       => ['nullable', 'integer', 'min:0'],
            'is_default'                       => ['nullable', 'boolean'],
            'is_active'                        => ['nullable', 'boolean'],
            'image'                            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'attributes'                       => ['nullable', 'array'],

            'attributes.*.attribute_id'        => ['required_with:attributes', 'integer', 'exists:attributes,id'],
            'attributes.*.attribute_option_id' => ['required_with:attributes', 'integer', 'exists:attribute_options,id'],
         
            // 'attributes.*.price_override'      => ['nullable', 'min:0'],

            'characteristics'                  => ['nullable', 'array'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZER
    |--------------------------------------------------------------------------
    */
    private function normalizePriceOverride($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        return (float) $value;
    }
}
