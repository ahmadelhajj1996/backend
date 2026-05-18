<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class AttributeOptionController extends Controller
{

 
    public function index(Request $request)
    {
        try {
            $query = AttributeOption::query();

            // // Filter by attribute_id
            // if ($request->has('attribute_id')) {
            //     $query->where('attribute_id', $request->attribute_id);
            // }

            // // Filter by value
            // if ($request->has('value')) {
            //     $query->where('value', 'like', "%{$request->value}%");
            // }

            // // Filter by color_code
            // if ($request->has('color_code')) {
            //     $query->where('color_code', $request->color_code);
            // }

            // // Filter by size_code
            // if ($request->has('size_code')) {
            //     $query->where('size_code', $request->size_code);
            // }

            // // Search across value field
            // if ($request->has('search')) {
            //     $search = $request->search;
            //     $query->where(function ($q) use ($search) {
            //         $q->where('value', 'like', "%{$search}%")
            //             ->orWhere('color_code', 'like', "%{$search}%")
            //             ->orWhere('size_code', 'like', "%{$search}%");
            //     });
            // }

            // // Sort options
            // // $sortField = $request->get('sort_by', 'sort_order');
            // // $sortOrder = $request->get('sort_order', 'asc');
            // // $allowedSortFields = ['id', 'attribute_id', 'value', 'sort_order', 'created_at', 'color_code', 'size_code'];

            // // if (in_array($sortField, $allowedSortFields)) {
            // //     $query->orderBy($sortField, $sortOrder);
            // // } else {
            // //     $query->orderBy('sort_order', 'asc');
            // // }

            // // Include attribute relationship if requested
            // if ($request->has('with_attribute') && $request->boolean('with_attribute')) {
            //     $query->with('attribute');
            // }

            $perPage = $request->get('per_page', 15);
            $attributeOptions = $query->paginate($perPage);

            return $this->successResponse($attributeOptions, 'Attribute options retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve attribute options: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validateAttributeOption($request);

            // Additional validation based on attribute type
            $attribute = Attribute::find($validated['attribute_id']);
            if (!$attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $this->validateOptionByAttributeType($attribute, $validated);

            $attributeOption = DB::transaction(function () use ($validated) {
                return AttributeOption::create($validated);
            });

            // Load relationship for response
            $attributeOption->load('attribute');

            return $this->createdResponse($attributeOption, 'Attribute option created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create attribute option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified attribute option.
     */
    public function show($id)
    {
        try {
            $attributeOption = AttributeOption::with('attribute')->find($id);

            if (!$attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            return $this->successResponse($attributeOption, 'Attribute option retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve attribute option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified attribute option.
     */
    public function update(Request $request, $id)
    {
        try {
            $attributeOption = AttributeOption::find($id);

            if (!$attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            $validated = $this->validateAttributeOption($request, $attributeOption->id);

            // Additional validation based on attribute type
            $attribute = Attribute::find($validated['attribute_id'] ?? $attributeOption->attribute_id);
            if (!$attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $this->validateOptionByAttributeType($attribute, $validated, $attributeOption);

            $updatedAttributeOption = DB::transaction(function () use ($attributeOption, $validated) {
                $attributeOption->update($validated);
                return $attributeOption->fresh();
            });

            $updatedAttributeOption->load('attribute');

            return $this->updatedResponse($updatedAttributeOption, 'Attribute option updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update attribute option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified attribute option.
     */
    public function destroy($id)
    {
        try {
            $attributeOption = AttributeOption::find($id);

            if (!$attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            DB::transaction(function () use ($attributeOption) {
                $attributeOption->delete();
            });

            return $this->deletedResponse('Attribute option deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete attribute option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restore a soft-deleted attribute option.
     */
    public function restore($id)
    {
        try {
            $attributeOption = AttributeOption::withTrashed()->find($id);

            if (!$attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            if (!$attributeOption->trashed()) {
                return $this->errorResponse('Attribute option is not deleted', 400);
            }

            DB::transaction(function () use ($attributeOption) {
                $attributeOption->restore();
            });

            return $this->successResponse($attributeOption->fresh(), 'Attribute option restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore attribute option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Permanently delete a soft-deleted attribute option.
     */
    public function forceDelete($id)
    {
        try {
            $attributeOption = AttributeOption::withTrashed()->find($id);

            if (!$attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            DB::transaction(function () use ($attributeOption) {
                $attributeOption->forceDelete();
            });

            return $this->deletedResponse('Attribute option permanently deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to permanently delete attribute option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk update sort order for multiple attribute options.
     */
    public function bulkUpdateSortOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'options' => 'required|array',
                'options.*.id' => 'required|exists:attribute_options,id',
                'options.*.sort_order' => 'required|min:0'
            ]);

            DB::transaction(function () use ($validated) {
                foreach ($validated['options'] as $item) {
                    AttributeOption::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
                }
            });

            return $this->successResponse(null, 'Sort order updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update sort order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get options by attribute ID.
     */
    public function getByAttribute($attributeId)
    {
        try {
            $attribute = Attribute::find($attributeId);

            if (!$attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $options = AttributeOption::where('attribute_id', $attributeId)
                ->orderBy('sort_order')
                ->get();

            return $this->successResponse($options, 'Options retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve options: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get options by attribute slug (for frontend).
     */
    public function getByAttributeSlug($slug)
    {
        try {
            $attribute = Attribute::where('slug', $slug)->first();

            if (!$attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $options = AttributeOption::where('attribute_id', $attribute->id)
                ->orderBy('sort_order')
                ->get();

            $response = [
                'attribute' => $attribute,
                'options' => $options
            ];

            return $this->successResponse($response, 'Options retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve options: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate attribute option data.
     */
    private function validateAttributeOption(Request $request, $ignoreId = null)
    {
        $uniqueRule = Rule::unique('attribute_options', 'value')
            ->where('attribute_id', $request->attribute_id);

        if ($ignoreId) {
            $uniqueRule = $uniqueRule->ignore($ignoreId);
        }

        return $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'value' => [
                'required',
                'string',
                'max:255',
                $uniqueRule
            ],
            'color_code' => 'nullable|string|max:50|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'size_code' => 'nullable|string|max:10',
            'sort_order' => 'nullable|min:0'
        ]);
    }

    /**
     * Validate option based on attribute type.
     */
    private function validateOptionByAttributeType($attribute, $validated, $existingOption = null)
    {
        $errors = [];

        // Color code validation for color attributes
        if ($attribute->type === 'color') {
            if (empty($validated['color_code']) && !$existingOption?->color_code) {
                $errors['color_code'] = ['Color code is required for color attributes'];
            }
        }

        // Size code validation for size attributes
        if ($attribute->type === 'size') {
            if (empty($validated['size_code']) && !$existingOption?->size_code) {
                $errors['size_code'] = ['Size code is required for size attributes'];
            }

            // Validate size code format (XS, S, M, L, XL, XXL, etc.)
            if (!empty($validated['size_code'])) {
                $validSizeCodes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XS/S', 'S/M', 'M/L', 'L/XL'];
                if (!in_array(strtoupper($validated['size_code']), $validSizeCodes)) {
                    $errors['size_code'] = ['Invalid size code format'];
                }
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return true;
    }
}