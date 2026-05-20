<?php

namespace App\Http\Controllers;

use App\Models\VariationAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VariationAttributeController extends Controller
{
    /**
     * Display a listing of variation attributes.
     */
    public function index(Request $request)
    {
        try {

            $query = VariationAttribute::with([
                'variation',
                'attribute',
                'option.attribute',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Filters
            |--------------------------------------------------------------------------
            */

            if ($request->filled('variation_id')) {
                $query->where('variation_id', $request->variation_id);
            }

            if ($request->filled('attribute_id')) {
                $query->where('attribute_id', $request->attribute_id);
            }

            if ($request->filled('attribute_option_id')) {
                $query->where('attribute_option_id', $request->attribute_option_id);
            }

            /*
            |--------------------------------------------------------------------------
            | Search
            |--------------------------------------------------------------------------
            */

            if ($request->filled('search')) {

                $search = $request->search;

                $query->where(function ($q) use ($search) {

                    $q->whereHas('variation', function ($vq) use ($search) {
                        $vq->where('sku', 'like', "%{$search}%");
                    })

                    ->orWhereHas('option', function ($oq) use ($search) {
                        $oq->where('name', 'like', "%{$search}%");
                    })

                    ->orWhereHas('attribute', function ($aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%");
                    });

                });
            }

            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            $sortField = $request->get('sort_by', 'id');

            $sortOrder = $request->get('sort_order', 'asc');

            $allowedSortFields = [
                'id',
                'variation_id',
                'attribute_id',
                'attribute_option_id',
                'created_at',
            ];

            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'id';
            }

            $query->orderBy($sortField, $sortOrder);

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            $perPage = (int) $request->get('per_page', 15);

            $variationAttributes = $query->paginate($perPage);

            return $this->successResponse(
                $variationAttributes,
                'Variation attributes retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve variation attributes: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Store a newly created variation attribute.
     */
    public function store(Request $request)
    {
        try {

            $validated = $this->validateVariationAttribute($request);

            $variationAttribute = DB::transaction(function () use ($validated) {

                return VariationAttribute::create($validated);

            });

            return $this->createdResponse(
                $variationAttribute->load([
                    'variation',
                    'attribute',
                    'option.attribute',
                ]),
                'Variation attribute created successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to create variation attribute: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified variation attribute.
     */
    public function show($id)
    {
        try {

            $variationAttribute = VariationAttribute::with([
                'variation',
                'attribute',
                'option.attribute',
            ])->find($id);

            if (!$variationAttribute) {
                return $this->notFoundResponse(
                    'Variation attribute not found'
                );
            }

            return $this->successResponse(
                $variationAttribute,
                'Variation attribute retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve variation attribute: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Update the specified variation attribute.
     */
    public function update(Request $request, $id)
    {
        try {

            $variationAttribute = VariationAttribute::find($id);

            if (!$variationAttribute) {
                return $this->notFoundResponse(
                    'Variation attribute not found'
                );
            }

            $validated = $this->validateVariationAttribute($request);

            $updated = DB::transaction(function () use (
                $variationAttribute,
                $validated
            ) {

                $variationAttribute->update($validated);

                return $variationAttribute->fresh();

            });

            return $this->updatedResponse(
                $updated->load([
                    'variation',
                    'attribute',
                    'option.attribute',
                ]),
                'Variation attribute updated successfully'
            );

        } catch (ValidationException $e) {

            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to update variation attribute: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified variation attribute.
     */
    public function destroy($id)
    {
        try {

            $variationAttribute = VariationAttribute::find($id);

            if (!$variationAttribute) {

                return $this->notFoundResponse(
                    'Variation attribute not found'
                );
            }

            DB::transaction(function () use ($variationAttribute) {

                $variationAttribute->delete();

            });

            return $this->deletedResponse(
                'Variation attribute deleted successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to delete variation attribute: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all attributes for a specific variation.
     */
    public function getByVariation($variationId)
    {
        try {

            $attributes = VariationAttribute::where(
                'variation_id',
                $variationId
            )

            ->with([
                'attribute',
                'option.attribute',
            ])

            ->get();

            return $this->successResponse(
                $attributes,
                'Variation attributes retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve variation attributes: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Validate variation attribute data.
     */
    private function validateVariationAttribute(Request $request): array
    {
        $validated = $request->validate([

            'variation_id' => [
                'required',
                'exists:variations,id',
            ],

            'attribute_id' => [
                'required',
                'exists:attributes,id',
            ],

            'attribute_option_id' => [
                'required',
                'exists:attribute_options,id',
            ],

        ]);
 
        
        $optionExists = DB::table('attribute_options')

            ->where('id', $validated['attribute_option_id'])

            ->where('attribute_id', $validated['attribute_id'])

            ->exists();

        if (!$optionExists) {

            throw ValidationException::withMessages([

                'attribute_option_id' => [
                    'The selected option does not belong to the selected attribute.',
                ],

            ]);
        }

        return $validated;
    }
}