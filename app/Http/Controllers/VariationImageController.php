<?php
namespace App\Http\Controllers;

use App\Models\Variation;
use App\Models\VariationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Helpers\ImageHelper;

class VariationImageController extends Controller
{

    public function index(Request $request)
    {
        try {
            $query = VariationImage::with([
                'variation.product',
            ]);

            if ($request->has('variation_id')) {
                $query->where('variation_id', $request->variation_id);
            }

            if ($request->has('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('path', 'like', "%{$search}%")
                        ->orWhereHas('variation', function ($vq) use ($search) {
                            $vq->where('sku', 'like', "%{$search}%");
                        });
                });
            }

            $sortField = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'asc');

            $allowedSortFields = [
                'id',
                'variation_id',
                'created_at',
            ];

            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('id', 'asc');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);

            $variationImages = $query->paginate($perPage);

            return $this->successResponse(
                $variationImages,
                'Variation images retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve variation images: ' . $e->getMessage(),
                500
            );
        }
    }

public function store(Request $request)
{
    try {

        $validated = $request->validate([
            'variation_id' => [
                'required',
                'integer',
                'exists:variations,id',
            ],

            'path' => [
                'required',
                'array',
            ],

            'path.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $variation = Variation::find($validated['variation_id']);

        if (! $variation) {
            return $this->errorResponse(
                'Variation not found',
                422
            );
        }

        $images = DB::transaction(function () use ($request, $validated) {

            $created = [];

            foreach ($request->file('path') as $file) {

                $uploadedPath = ImageHelper::upload(
                    $file,
                    'variationimages'
                );

                $created[] = VariationImage::create([
                    'variation_id' => $validated['variation_id'],
                    'path' => $uploadedPath,
                ]);
            }

            return $created;
        });

        return $this->createdResponse(
            $images,
            'Variation images created successfully'
        );

    } catch (ValidationException $e) {

        return $this->validationErrorResponse(
            $e->errors(),
            'Validation failed'
        );

    } catch (\Exception $e) {

        return $this->errorResponse(
            'Failed to create variation images: ' . $e->getMessage(),
            500
        );
    }
}

 
    public function show($id)
    {
        try {
            $variationImage = VariationImage::with([
                'variation.product',
            ])->find($id);

            if (! $variationImage) {
                return $this->notFoundResponse(
                    'Variation image not found'
                );
            }

            return $this->successResponse(
                $variationImage,
                'Variation image retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve variation image: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified variation image.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    // public function update(Request $request, $id)
    // {
    //     try {
    //         $variationImage = VariationImage::find($id);

    //         if (! $variationImage) {
    //             return $this->notFoundResponse(
    //                 'Variation image not found'
    //             );
    //         }

    //         $validated = $this->validateVariationImage($request);

    //         // If variation_id changed, verify it exists
    //         if (
    //             isset($validated['variation_id']) &&
    //             $validated['variation_id'] != $variationImage->variation_id
    //         ) {
    //             $variation = Variation::find($validated['variation_id']);

    //             if (! $variation) {
    //                 return $this->errorResponse(
    //                     'The selected variation does not exist.',
    //                     422
    //                 );
    //             }
    //         }

    //             if ($request->boolean('remove_path')) {

    //                 if ($variationImage->path) {
    //                     ImageHelper::delete($variationImage->path);
    //                 }

    //                 $validated['path'] = null;
    //             }

    //             if ($request->hasFile('path')) {

    //                 $newImage = ImageHelper::upload(
    //                     $request->file('path'),
    //                     'variationimages'
    //                 );

    //                 if ($variationImage->path) {
    //                     ImageHelper::delete($variationImage->path);
    //                 }

    //                 $validated['path'] = $newImage;
    //             }

    //             $updated = DB::transaction(function () use (
    //             $variationImage,
    //             $validated
    //         ) {
    //             $variationImage->update($validated);

    //             return $variationImage->fresh();
    //         });

    //         return $this->updatedResponse(
    //             $updated->load([
    //                 'variation.product',
    //             ]),
    //             'Variation image updated successfully'
    //         );
    //     } catch (ValidationException $e) {
    //         return $this->validationErrorResponse(
    //             $e->errors(),
    //             'Validation failed'
    //         );
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(
    //             'Failed to update variation image: ' . $e->getMessage(),
    //             500
    //         );
    //     }
    // }

    /**
     * Remove the specified variation image.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $variationImage = VariationImage::find($id);

            if (! $variationImage) {
                return $this->notFoundResponse(
                    'Variation image not found'
                );
            }

            DB::transaction(function () use ($variationImage) {
                if ($variationImage->path) {
                    ImageHelper::delete($variationImage->path);
                }
                $variationImage->delete();
            });

            return $this->deletedResponse(
                'Variation image deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete variation image: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all images for a specific variation.
     *
     * @param int $variationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByVariation($variationId)
    {
        try {
            $variation = Variation::find($variationId);

            if (! $variation) {
                return $this->notFoundResponse(
                    'Variation not found'
                );
            }

            $images = VariationImage::where(
                'variation_id',
                $variationId
            )
                ->with([
                    'variation.product',
                ])
                ->get();

            return $this->successResponse(
                $images,
                'Variation images retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve variation images: ' . $e->getMessage(),
                500
            );
        }
    }

        private function validateVariationImage(Request $request)
        {
            return $request->validate([
                'variation_id' => [
                    'required',
                    'integer',
                    'exists:variations,id',
                ],

                'path' => [
                    $request->isMethod('post') ? 'required' : 'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],

                'remove_path' => [
                    'nullable',
                    'boolean',
                ],
            ]);
        }

        public function sync(Request $request)
{
    try {

        $validated = $request->validate([
            'variation_id' => ['required', 'exists:variations,id'],

            'existing_ids' => ['nullable', 'string'],

            'path' => ['nullable', 'array'],

            'path.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $variationId = $validated['variation_id'];

        $existingIds = json_decode($validated['existing_ids'] ?? '[]', true);

        return DB::transaction(function () use ($variationId, $existingIds, $request) {

            /*
            |--------------------------------------------------------------------------
            | DELETE removed images
            |--------------------------------------------------------------------------
            */

            $dbImages = VariationImage::where('variation_id', $variationId)->get();

            foreach ($dbImages as $img) {

                if (!in_array($img->id, $existingIds)) {

                    if ($img->path) {
                        ImageHelper::delete($img->path);
                    }

                    $img->delete();
                }
            }

            /*
            |--------------------------------------------------------------------------
            | ADD new images
            |--------------------------------------------------------------------------
            */

            $created = [];

            if ($request->hasFile('path')) {

                foreach ($request->file('path') as $file) {

                    $uploadedPath = ImageHelper::upload(
                        $file,
                        'variationimages'
                    );

                    $created[] = VariationImage::create([
                        'variation_id' => $variationId,
                        'path' => $uploadedPath,
                    ]);
                }
            }

            return $this->successResponse([
                'deleted' => true,
                'created' => $created,
            ], 'Images synced successfully');

        });

    } catch (\Exception $e) {

        return $this->errorResponse(
            'Failed to sync variation images: ' . $e->getMessage(),
            500
        );
    }
}
}
