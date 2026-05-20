<?php
namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttributeOptionController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = AttributeOption::with(['attribute']);

            // Filter by attribute_id
            if ($request->filled('attribute_id')) {
                $query->where('attribute_id', $request->attribute_id);
            }

            // Filter by value
            if ($request->filled('value')) {
                $query->where('value', 'like', "%{$request->value}%");
            }

            // Filter by color_code
            if ($request->filled('color_code')) {
                $query->where('color_code', $request->color_code);
            }

            // Filter by size_code
            if ($request->filled('size_code')) {
                $query->where('size_code', $request->size_code);
            }

            // Search
            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('value', 'like', "%{$search}%")
                        ->orWhere('color_code', 'like', "%{$search}%")
                        ->orWhere('size_code', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');

            $allowedSortFields = [
                'id',
                'attribute_id',
                'value',
                'sort_order',
                'created_at',
                'color_code',
                'size_code',
            ];

            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            } else {
                $query->orderBy('sort_order', 'asc');
            }

            $perPage          = $request->get('per_page', 15);
            $attributeOptions = $query->paginate($perPage);

            return $this->successResponse(
                $attributeOptions,
                'Attribute options retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve attribute options: ' . $e->getMessage(),
                500
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $this->validateAttributeOption($request);

            $attribute = Attribute::find($validated['attribute_id']);
            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $this->validateOptionByAttributeType($attribute, $validated);

            $attributeOption = DB::transaction(function () use ($validated) {
                return AttributeOption::create($validated);
            });

            $attributeOption->load('attribute');

            return $this->createdResponse(
                $attributeOption,
                'Attribute option created successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to create attribute option: ' . $e->getMessage(),
                500
            );
        }
    }

    public function show($id)
    {
        try {
            $attributeOption = AttributeOption::with('attribute')->find($id);

            if (! $attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            return $this->successResponse(
                $attributeOption,
                'Attribute option retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve attribute option: ' . $e->getMessage(),
                500
            );
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $attributeOption = AttributeOption::find($id);

            if (! $attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            $validated = $this->validateAttributeOption($request, $attributeOption->id);

            $attribute = Attribute::find(
                $validated['attribute_id'] ?? $attributeOption->attribute_id
            );

            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $this->validateOptionByAttributeType(
                $attribute,
                $validated,
                $attributeOption
            );

            $updated = DB::transaction(function () use ($attributeOption, $validated) {
                $attributeOption->update($validated);
                return $attributeOption->fresh();
            });

            $updated->load('attribute');

            return $this->updatedResponse(
                $updated,
                'Attribute option updated successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to update attribute option: ' . $e->getMessage(),
                500
            );
        }
    }

    public function destroy($id)
    {
        try {
            $attributeOption = AttributeOption::find($id);

            if (! $attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            $attributeOption->delete();

            return $this->deletedResponse('Attribute option deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to delete attribute option: ' . $e->getMessage(),
                500
            );
        }
    }

    private function validateAttributeOption(Request $request, $ignoreId = null)
    {
        $uniqueRule = Rule::unique('attribute_options', 'value')
            ->where('attribute_id', $request->attribute_id);

        if ($ignoreId) {
            $uniqueRule = $uniqueRule->ignore($ignoreId);
        }

        return $request->validate([
            'attribute_id' => 'required|exists:attributes,id',
            'value'        => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'color_code'   => 'nullable|string|max:50|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'size_code'    => 'nullable|string|max:10',
            'sort_order'   => 'nullable|integer|min:0',
        ]);
    }

    private function validateOptionByAttributeType($attribute, $validated, $existingOption = null)
    {
        $errors = [];

        $color = $validated['color_code'] ?? null;
        $size  = $validated['size_code'] ?? null;

        if ($attribute->type === 'color') {
            if (! $color && ! $existingOption?->color_code) {
                $errors['color_code'] = ['Color code is required for color attributes'];
            }
        }

        if ($attribute->type === 'size') {
            if (! $size && ! $existingOption?->size_code) {
                $errors['size_code'] = ['Size code is required for size attributes'];
            }

            if ($size) {
                $valid = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XS/S', 'S/M', 'M/L', 'L/XL'];

                if (! in_array(strtoupper($size), $valid)) {
                    $errors['size_code'] = ['Invalid size code format'];
                }
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return true;
    }
}
