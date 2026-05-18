<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Characteristic;
use App\Models\Product;
use App\Models\Variation;
use App\Models\VariationAttribute;
use App\Models\VariationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VariationController extends Controller
{
    /**
     * Display a listing of variations.
     */
    public function index(Request $request)
    {
        try {

            $query = Variation::with([
                'product',
                'images',
                'attributes.option.attribute',
                'characteristics',
            ]);

            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            $perPage = $request->get('per_page', 15);

            $variations = $query->paginate($perPage);

            return $this->successResponse(
                $variations,
                'Variations retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve variations: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created variation.
     */
    public function store(Request $request)
    {
        try {

            $validated = $this->validateVariation($request);

            $product = Product::find($validated['product_id']);

            if (! $product) {
                return $this->errorResponse(
                    'The selected product does not exist.',
                    422
                );
            }

            $variation = DB::transaction(function () use (
                $validated,
                $request
            ) {

                // unset old default
                if (! empty($validated['is_default'])) {

                    Variation::where(
                        'product_id',
                        $validated['product_id']
                    )->update([
                        'is_default' => false,
                    ]);
                }

                // create variation
                $variation = Variation::create([
                    'product_id' => $validated['product_id'],
                    'sku'        => $validated['sku'],
                    'price'      => $validated['price'],
                    'quantity'   => $validated['quantity'] ?? 0,
                    'is_default' => $validated['is_default'] ?? false,
                    'sold_count' => $validated['sold_count'] ?? 0,
                ]);

                if (! empty($validated['attributes'])) {

                    foreach ($validated['attributes'] as $attribute) {

                        VariationAttribute::create([
                            'variation_id'        => $variation->id,
                            'attribute_id'        => $attribute['attribute_id'],
                            'attribute_option_id' => $attribute['attribute_option_id'],
                        ]);
                    }
                }

                if (! empty($validated['characteristics'])) {

                    foreach ($validated['characteristics'] as $characteristic) {

                        Characteristic::create([
                            'variation_id' => $variation->id,
                            'attribute'    => $characteristic['attribute'],
                        ]);
                    }
                }

                if ($request->hasFile('images')) {

                    foreach ($request->file('images') as $image) {

                        $path = ImageHelper::upload(
                            $image,
                            'variations'
                        );

                        VariationImage::create([
                            'variation_id' => $variation->id,
                            'path'         => $path,
                        ]);
                    }
                }

                return $variation;
            });

            return $this->createdResponse(
                $variation->load([
                    'product',
                    'images',
                    'attributes.option.attribute',
                ]),
                'Variation created successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to create variation: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified variation.
     */
    public function show($id)
    {
        try {

            $variation = Variation::with([
                'product',
                'images',
                'attributes.option.attribute',
            ])->find($id);

            if (! $variation) {

                return $this->notFoundResponse(
                    'Variation not found'
                );
            }

            return $this->successResponse(
                $variation,
                'Variation retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve variation: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified variation.
     */
    public function update(Request $request, $id)
    {
        try {

            $variation = Variation::with([
                'images',
                'attributes',
            ])->find($id);

            if (! $variation) {

                return $this->notFoundResponse(
                    'Variation not found'
                );
            }

            $validated = $this->validateVariation(
                $request,
                $variation->id
            );

            $updatedVariation = DB::transaction(function () use (
                $variation,
                $validated,
                $request
            ) {

                $productId = $validated['product_id'] ?? $variation->product_id;

                /**
                 * Handle default variation
                 */
                if (! empty($validated['is_default'])) {

                    Variation::where(
                        'product_id',
                        $productId
                    )
                        ->where('id', '!=', $variation->id)
                        ->update([
                            'is_default' => false,
                        ]);
                }

                /**
                 * Update variation
                 */
                $variation->update([
                    'product_id' => $validated['product_id'],
                    'sku'        => $validated['sku'],
                    'price'      => $validated['price'],
                    'quantity'   => $validated['quantity'] ?? 0,
                    'is_default' => $validated['is_default'] ?? false,
                    'sold_count' => $validated['sold_count'] ?? 0,
                ]);

                /**
                 * Replace variation attributes
                 */

                $variation->attributes()->delete();
                $variation->characteristics()->delete();

                if (! empty($validated['characteristics'])) {

                    foreach ($validated['characteristics'] as $characteristic) {

                        Characteristic::create([
                            'variation_id' => $variation->id,
                            'attribute'    => $characteristic['attribute'],
                        ]);
                    }
                }
                if (! empty($validated['attributes'])) {
                    foreach ($validated['attributes'] as $attribute) {
                        VariationAttribute::create([
                            'variation_id'        => $variation->id,
                            'attribute_id'        => $attribute['attribute_id'],
                            'attribute_option_id' => $attribute['attribute_option_id'],
                        ]);
                    }
                }

                /**
                 * Add new images
                 */
                if ($request->hasFile('images')) {

                    foreach ($request->file('images') as $image) {

                        $path = ImageHelper::upload(
                            $image,
                            'variations'
                        );

                        VariationImage::create([
                            'variation_id' => $variation->id,
                            'path'         => $path,
                        ]);
                    }
                }

                /**
                 * Delete selected images
                 */
                if (! empty($validated['deleted_images'])) {

                    $images = VariationImage::whereIn(
                        'id',
                        $validated['deleted_images']
                    )
                        ->where('variation_id', $variation->id)
                        ->get();

                    foreach ($images as $image) {

                        ImageHelper::delete($image->path);

                        $image->delete();
                    }
                }

                return $variation->fresh();
            });

            return $this->updatedResponse(
                $updatedVariation->load([
                    'product',
                    'images',
                    'attributes.option.attribute',
                ]),
                'Variation updated successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to update variation: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified variation.
     */
    public function destroy($id)
    {
        try {

            $variation = Variation::with([
                'images',
                'attributes',
            ])->find($id);

            if (! $variation) {

                return $this->notFoundResponse(
                    'Variation not found'
                );
            }

            DB::transaction(function () use ($variation) {

                /**
                 * Delete images from storage
                 */
                foreach ($variation->images as $image) {

                    ImageHelper::delete($image->path);
                }

                /**
                 * Delete relations
                 */
                $variation->images()->delete();

                $variation->attributes()->delete();

                /**
                 * Delete variation
                 */
                $variation->delete();
            });

            return $this->deletedResponse(
                'Variation deleted successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to delete variation: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Bulk update stock quantities.
     */
    public function bulkUpdateStock(Request $request)
    {
        try {

            $validated = $request->validate([
                'variations'            => 'required|array',
                'variations.*.id'       => 'required|exists:variations,id',
                'variations.*.quantity' => 'required|integer|min:0',
            ]);

            DB::transaction(function () use ($validated) {

                foreach ($validated['variations'] as $item) {

                    Variation::where('id', $item['id'])
                        ->update([
                            'quantity' => $item['quantity'],
                        ]);
                }
            });

            return $this->successResponse(
                null,
                'Stock quantities updated successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to update stock: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get variations by product.
     */
    public function getByProduct($productId)
    {
        try {

            $product = Product::find($productId);

            if (! $product) {

                return $this->notFoundResponse(
                    'Product not found'
                );
            }

            $variations = Variation::with([
                'images',
                'attributes.option.attribute',
            ])
                ->where('product_id', $productId)
                ->orderBy('is_default', 'desc')
                ->orderBy('price', 'asc')
                ->get();

            return $this->successResponse(
                $variations,
                'Product variations retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve product variations: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Validate variation data.
     */
    private function validateVariation(
        Request $request,
        $ignoreId = null
    ) {

        $uniqueSkuRule = Rule::unique(
            'variations',
            'sku'
        );

        if ($ignoreId) {
            $uniqueSkuRule->ignore($ignoreId);
        }

        return $request->validate([

            'product_id'                       => [
                'required',
                'exists:products,id',
            ],

            'sku'                              => [
                'required',
                'string',
                'max:100',
                $uniqueSkuRule,
            ],

            'price'                            => [
                'required',
                'numeric',
                'min:0',
            ],

            'quantity'                         => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_default'                       => [
                'nullable',
                'boolean',
            ],

            'sold_count'                       => [
                'nullable',
                'integer',
                'min:0',
            ],

            /**
             * Images
             */
            'images'                           => [
                'nullable',
                'array',
            ],

            'images.*'                         => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            'deleted_images'                   => [
                'nullable',
                'array',
            ],

            'deleted_images.*'                 => [
                'integer',
                'exists:variation_images,id',
            ],

            /**
             * Attributes
             */
            'attributes'                       => ['nullable', 'array'],
            'attributes.*.attribute_id'        => ['required', 'integer'],
            'attributes.*.attribute_option_id' => ['required', 'integer'],

            'characteristics'                  => ['nullable', 'array'],
            'characteristics.*.attribute'      => ['required', 'string'],

        ]);
    }
}
