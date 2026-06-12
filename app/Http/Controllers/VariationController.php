<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Models\Characteristic;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Variation;
use App\Services\VariationRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VariationController extends Controller
{

    public function index(Request $request)
    {
        $query = Variation::with([
            'product:id,name',
            'images',
            'characteristics',
        ]);

        if ($request->product_id) {
            $query->where('product_id', $request->product_id);
        }

        $variations = $query->latest()->get();

        $allOptionIds = $variations->pluck('attributes')
            ->filter()
            ->flatMap(fn($attrs) => array_values($attrs))
            ->unique()
            ->toArray();

        $attributeLookups = [];
        if (! empty($allOptionIds)) {
            $attributeLookups = DB::table('attribute_options')
                ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
                ->whereIn('attribute_options.id', $allOptionIds)
                ->select(
                    'attributes.id as attribute_id',
                    'attributes.name as attribute_name',
                    'attribute_options.id as option_id',
                    'attribute_options.value as option_value'
                )
                ->get()
                ->keyBy('option_id')
                ->toArray();
        }

        // --- STEP 2: GROUP AND PROCESS DATA ---
        $groupedData = $variations->groupBy('group_key')->map(function ($items, $groupKey) use ($attributeLookups) {

            $groupImages = $items->pluck('images')->flatten(1)->unique('id')->values();

            $groupCharacteristics = $items->pluck('characteristics')->flatten(1)->unique('id')->values();

            // Map every item's raw attributes to your detailed structured format
            $itemsWithDetailedAttrs = $items->map(function ($item) use ($attributeLookups) {
                $formattedAttrs = [];
                $rawAttrs       = $item->attributes ?? [];

                foreach ($rawAttrs as $attrId => $optionId) {
                    if (isset($attributeLookups[$optionId])) {
                        $lookup = $attributeLookups[$optionId];

                        $formattedAttrs[$lookup->attribute_id] = [
                            'attribute_id'   => $lookup->attribute_id,
                            'attribute_name' => $lookup->attribute_name,
                            'option_id'      => $lookup->option_id,
                            'option_value'   => $lookup->option_value,
                        ];
                    }
                }

                $item->computed_detailed_attributes = $formattedAttrs;
                return $item;
            });

            $compareAttributes = function ($a, $b) {
                return ($a['option_id'] <=> $b['option_id']);
            };

            $sharedAttributes = [];
            if ($itemsWithDetailedAttrs->isNotEmpty()) {
                $sharedAttributes = $itemsWithDetailedAttrs->first()->computed_detailed_attributes;

                foreach ($itemsWithDetailedAttrs as $item) {
                    // Fixed using array_uintersect_assoc
                    $sharedAttributes = array_uintersect_assoc(
                        $sharedAttributes,
                        $item->computed_detailed_attributes,
                        $compareAttributes
                    );
                }
            }

            $mappedItems = $itemsWithDetailedAttrs->map(function ($item) use ($sharedAttributes, $compareAttributes) {

                // Fixed using array_udiff_assoc
                $specialAttributes = array_udiff_assoc(
                    $item->computed_detailed_attributes,
                    $sharedAttributes,
                    $compareAttributes
                );

                return [
                    'id'                       => $item->id,
                    'product_id'               => $item->product_id,
                    'sku'                      => $item->sku,
                    'special_attributes'       => array_values($specialAttributes),
                    'sell_price'               => $item->sell_price,
                    'base_price'               => $item->base_price,
                    'sell_rate'                => $item->sell_rate,
                    'buy_price'                => $item->buy_price,
                    'base_buy_price'           => $item->base_buy_price,
                    'buy_rate'                 => $item->buy_rate,
                    'quantity'                 => $item->quantity,
                    'sold_count'               => $item->sold_count,
                    'cached_final_price'       => $item->cached_final_price,
                    'cached_profit'            => $item->cached_profit,
                    'cached_profit_percentage' => $item->cached_profit_percentage,
                    'is_default'               => $item->is_default,
                    'is_active'                => $item->is_active,
                    'created_at'               => $item->created_at,
                    'updated_at'               => $item->updated_at,
                    'product'                  => $item->product,
                ];
            });

            return [
                'group_key'       => $groupKey,
                'images'          => $groupImages,
                'attributes'      => array_values($sharedAttributes),
                'characteristics' => $groupCharacteristics,
                'items'           => $mappedItems,
            ];
        })->values();

        return $this->successResponse($groupedData, 'ok');
    }

    public function store(Request $request)
    {
        $validated = $this->validateVariation($request);
        $rates     = VariationRateService::snapshot();

        DB::beginTransaction();

        try {

            $product  = Product::findOrFail($validated['product_id']);
            $groupKey = $validated['group_key'] ?? Str::uuid()->toString();

            $created = [];

            foreach ($validated['variations'] as $index => $item) {

                $attributes = $item['attributes'] ?? [];
                ksort($attributes);

                $variation = Variation::create([
                    'product_id'     => $validated['product_id'],
                    'group_key'      => $groupKey,
                    'sku'            => Str::slug($product->name) . '-' . Str::random(16),
                    'attributes'     => $attributes,
                    'base_price'     => $item['base_price'],
                    'base_buy_price' => $item['base_buy_price'],
                    'sell_rate'      => $rates['sell_rate'],
                    'buy_rate'       => $rates['buy_rate'],
                    'sell_price'     => round($item['base_price'] * $rates['sell_rate']),
                    'buy_price'      => round($item['base_buy_price'] * $rates['buy_rate']),
                    'quantity'       => $item['quantity'],
                    'sold_count'     => 0,
                    'is_default'     => $index === 0,
                    'is_active'      => true,
                ]);

                $created[] = $variation;
            }

            $characteristicIds = [];
            foreach ($validated['characteristics'] ?? [] as $char) {
                if (! empty($char['name'])) {
                    // Find or create the master record to prevent global table duplication
                    $masterChar = Characteristic::firstOrCreate([
                        'name' => $char['name'],
                    ]);
                    $characteristicIds[] = $masterChar->id;
                }
            }

            $imageIds = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $i => $file) {

                    $hash = md5_file($file->getRealPath());

                    $image = ProductImage::firstOrCreate(
                        ['hash' => $hash],
                        [
                            'path'       => ImageHelper::upload($file, 'variations'),
                            'sort_order' => $i,
                        ]
                    );

                    $imageIds[] = $image->id;
                }
            }

            foreach ($created as $v) {
                if (! empty($characteristicIds)) {
                    $v->characteristics()->sync($characteristicIds);
                }
                if (! empty($imageIds)) {
                    $v->images()->sync($imageIds);
                }
            }

            DB::commit();

            return $this->successResponse($created, 'Variations stored successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $groupKey)
    {
        $validated = $this->validateVariation($request);

        $rates = VariationRateService::snapshot();

        if (! $groupKey) {
            return $this->errorResponse("Missing group_key", 422);
        }

        DB::beginTransaction();

        try {

            $product = Product::findOrFail($validated['product_id']);

            $characteristicIds = [];
            foreach ($validated['characteristics'] ?? [] as $char) {
                if (! empty($char['name'])) {
                    $masterChar = Characteristic::firstOrCreate([
                        'name' => $char['name'],
                    ]);
                    $characteristicIds[] = $masterChar->id;
                }
            }

            $allProcessedVariations = [];
            $broadcastItems         = [];

            foreach ($validated['variations'] as $index => $item) {
                $attributes = $item['attributes'] ?? [];
                ksort($attributes);

                $variationData = [
                    'product_id'     => $validated['product_id'],
                    'group_key'      => $groupKey,
                    'attributes'     => $attributes,
                    'base_price'     => $item['base_price'],
                    'base_buy_price' => $item['base_buy_price'],
                    'sell_rate'      => $rates['sell_rate'],
                    'buy_rate'       => $rates['buy_rate'],
                    'sell_price'     => round($item['base_price'] * $rates['sell_rate']),
                    'buy_price'      => round($item['base_buy_price'] * $rates['buy_rate']),
                    'quantity'       => $item['quantity'],
                    'is_default'     => $index === 0,
                    'is_active'      => 1,
                ];

                if (! empty($item['id'])) {
                    // ✅ Has ID — update existing, never delete
                    $variation = Variation::findOrFail($item['id']);
                    $variation->update($variationData);

                } elseif (! empty($item['sku'])) {
                    // ✅ No ID but has SKU — find by SKU to avoid duplicate creation
                    $variation = Variation::where('sku', $item['sku'])->first();

                    if ($variation) {
                        $variation->update($variationData);
                    } else {
                        $variationData['sku']        = $item['sku'];
                        $variationData['sold_count'] = 0;
                        $variation                   = Variation::create($variationData);
                    }

                } else {
                    // ✅ Truly new variation — create fresh
                    $variationData['sku']        = Str::slug($product->name) . '-' . Str::uuid();
                    $variationData['sold_count'] = 0;
                    $variation                   = Variation::create($variationData);
                }

                $broadcastItems[] = [
                    'id'       => $variation->id,
                    'quantity' => $variation->quantity,
                ];
                $allProcessedVariations[] = $variation;
            }

            // Smart image syncing
            $finalImageIds   = [];
            $hasImagePayload = false;

            if ($request->has('existing_images')) {
                $imagesInput = $request->input('existing_images', []);
                if (is_array($imagesInput) && count($imagesInput) > 0) {
                    $hasImagePayload = true;
                    foreach ($imagesInput as $imgData) {
                        if (
                            is_array($imgData) &&
                            isset($imgData['existing']) &&
                            ($imgData['existing'] === 'true' || $imgData['existing'] === true)
                        ) {
                            if (! empty($imgData['id']) && is_numeric($imgData['id']) && $imgData['id'] > 0) {
                                $finalImageIds[] = (int) $imgData['id'];
                            }
                        }
                    }
                }
            }

            if ($request->hasFile('images')) {
                $hasImagePayload = true;
                foreach ($request->file('images') as $i => $file) {
                    $hash = md5_file($file->getRealPath());

                    $image = ProductImage::firstOrCreate(
                        ['hash' => $hash],
                        [
                            'path'       => ImageHelper::upload($file, 'variations'),
                            'sort_order' => $i,
                        ]
                    );

                    $finalImageIds[] = $image->id;
                }
            }

            if (! $hasImagePayload) {
                $sampleVariation = Variation::where('group_key', $groupKey)->first();
                if ($sampleVariation) {
                    $finalImageIds = $sampleVariation->images()->pluck('product_images.id')->toArray();
                }
            }

            $finalImageIds = array_unique(array_filter($finalImageIds));

            foreach ($allProcessedVariations as $v) {
                $v->images()->sync($finalImageIds);
                $v->characteristics()->sync($characteristicIds);
            }

            DB::commit();

            foreach ($broadcastItems as $item) {
                broadcast(new \App\Events\VariationStockUpdated(
                    $item['id'],
                    $item['quantity']
                ));
            }

            return $this->successResponse($allProcessedVariations, 'Variations updated successfully');

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage(), 500);
        }
    }


    

    private function validateVariation(Request $request)
    {
        return $request->validate([
            'product_id'                  => ['required', 'exists:products,id'],
            'group_key'                   => ['nullable', 'string'],
            'variations'                  => ['required', 'array'],
            'variations.*.id'             => ['nullable', 'integer', 'exists:variations,id'],
            'variations.*.sku'            => ['nullable', 'string'],
            'variations.*.base_price'     => ['required', 'numeric'],
            'variations.*.base_buy_price' => ['required', 'numeric'],
            'variations.*.quantity'       => ['required', 'integer'],
            'variations.*.attributes'     => ['required', 'array'],
            'characteristics'             => ['nullable', 'array'],
            'characteristics.*.name'      => ['nullable', 'string'],
            'images'                      => ['nullable', 'array'],
            'images.*'                    => ['image'],
        ]);
    }

    public function destroy($groupKey)
    {
        if (! $groupKey) {
            return $this->errorResponse("Missing group_key", 422);
        }

        // 1. Fetch variations with their images
        $variations = Variation::with('images')->where('group_key', $groupKey)->get();

        if ($variations->isEmpty()) {
            return response()->json(['message' => 'No variations found for this group key'], 404);
        }

        DB::beginTransaction();

        try {
            $imageIds = $variations->flatMap(fn($v) => $v->images->pluck('id'))->unique()->toArray();

            $imagesToDelete = [];
            if (! empty($imageIds)) {
                $images = ProductImage::whereIn('id', $imageIds)->get();

                foreach ($images as $image) {
                    $usedByOtherGroups = DB::table('product_image_variations')
                        ->join('variations', 'product_image_variations.variation_id', '=', 'variations.id')
                        ->where('product_image_variations.product_image_id', $image->id)
                        ->where('variations.group_key', '!=', $groupKey)
                        ->exists();

                    if (! $usedByOtherGroups) {
                        $imagesToDelete[] = $image;
                    }
                }
            }

            foreach ($variations as $v) {
                $v->images()->detach();
                $v->characteristics()->detach();
            }

            foreach ($imagesToDelete as $image) {
                if ($image->path) {
                    ImageHelper::delete($image->path);
                }
                $image->delete();
            }

            Variation::where('group_key', $groupKey)->delete();

            DB::commit();
            return $this->deletedResponse();

        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->errorResponse($e->getMessage() . " on line " . $e->getLine(), 500);
        }
    }

}
