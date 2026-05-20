<?php
namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttributeController extends Controller
{

public function index(Request $request)
{
    try {
        $query = Attribute::with('options');

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('is_filterable')) {
            $query->where('is_filterable', $request->boolean('is_filterable'));
        }

        if ($request->has('is_required')) {
            $query->where('is_required', $request->boolean('is_required'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $sortField = $request->get('sort_by', 'sort_order');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSortFields = ['id', 'name', 'type', 'sort_order', 'created_at', 'is_filterable', 'is_required'];

        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('sort_order', 'asc');
        }

        $perPage = $request->get('per_page', 15);
        $attributes = $query->paginate($perPage);

        return $this->successResponse($attributes, 'Attributes retrieved successfully');
    } catch (\Exception $e) {
        return $this->errorResponse('Failed to retrieve attributes: ' . $e->getMessage(), 500);
    }
}   

    public function store(Request $request)
    {
        try {
            $validated = $this->validateAttribute($request);

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
                $originalSlug      = $validated['slug'];
                $count             = 1;
                while (Attribute::where('slug', $validated['slug'])->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $count++;
                }
            }

            $attribute = DB::transaction(function () use ($validated) {
                return Attribute::create($validated);
            });

            return $this->createdResponse($attribute, 'Attribute created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create attribute: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $attribute = Attribute::find($id);

            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $validated = $this->validateAttribute($request, $attribute->id);

            // Handle slug update
            if (isset($validated['name']) && empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
                // Ensure uniqueness
                $originalSlug = $validated['slug'];
                $count        = 1;
                while (Attribute::where('slug', $validated['slug'])->where('id', '!=', $attribute->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $count++;
                }
            }

            $updatedAttribute = DB::transaction(function () use ($attribute, $validated) {
                $attribute->update($validated);
                return $attribute->fresh();
            });

            return $this->updatedResponse($updatedAttribute, 'Attribute updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update attribute: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $attribute = Attribute::find($id);

            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            if ($attribute->options()->exists()) {
                return $this->cannotDeleteWithChildrenResponse(
                    $attribute->options()->count(),
                    'attribute'
                );
            }

            DB::transaction(function () use ($attribute) {
                $attribute->delete();
            });

            return $this->deletedResponse('Attribute deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete attribute: ' . $e->getMessage(), 500);
        }
    }

    public function restore($id)
    {
        try {
            $attribute = Attribute::withTrashed()->find($id);

            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            if (! $attribute->trashed()) {
                return $this->errorResponse('Attribute is not deleted', 400);
            }

            DB::transaction(function () use ($attribute) {
                $attribute->restore();
            });

            return $this->successResponse($attribute->fresh(), 'Attribute restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore attribute: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Permanently delete a soft-deleted attribute.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete($id)
    {
        try {
            $attribute = Attribute::withTrashed()->find($id);

            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            // Check if attribute has related values before force delete
            if ($attribute->values()->withTrashed()->exists()) {
                return $this->errorResponse(
                    'Cannot permanently delete attribute with existing values. Delete or reassign values first.',
                    422
                );
            }

            DB::transaction(function () use ($attribute) {
                $attribute->forceDelete();
            });

            return $this->deletedResponse('Attribute permanently deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to permanently delete attribute: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update attribute status (filterable).
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFilterable(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'is_filterable' => 'required|boolean',
            ]);

            $attribute = Attribute::find($id);

            if (! $attribute) {
                return $this->notFoundResponse('Attribute not found');
            }

            $attribute->update(['is_filterable' => $validated['is_filterable']]);

            return $this->updatedResponse($attribute, 'Attribute filterable status updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update filterable status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk update sort order for multiple attributes.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkUpdateSortOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'attributes'              => 'required|array',
                'attributes.*.id'         => 'required|exists:attributes,id',
                'attributes.*.sort_order' => 'required|min:0',
            ]);

            DB::transaction(function () use ($validated) {
                foreach ($validated['attributes'] as $item) {
                    Attribute::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
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
     * Get all attributes of a specific type.
     *
     * @param string $type
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByType($type)
    {
        try {
            $allowedTypes = ['text', 'select', 'color', 'size', 'checkbox', 'radio'];

            if (! in_array($type, $allowedTypes)) {
                return $this->errorResponse('Invalid attribute type', 400);
            }

            $attributes = Attribute::where('type', $type)
                ->orderBy('sort_order')
                ->get();

            return $this->successResponse($attributes, "{$type} attributes retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve attributes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get filterable attributes for frontend.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilterable()
    {
        try {
            $attributes = Attribute::where('is_filterable', true)
                ->orderBy('sort_order')
                ->with('values')
                ->get();

            return $this->successResponse($attributes, 'Filterable attributes retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve filterable attributes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate attribute data.
     *
     * @param Request $request
     * @param int|null $ignoreId
     * @return array
     */
    private function validateAttribute(Request $request, $ignoreId = null)
    {
        $uniqueSlugRule = Rule::unique('attributes', 'slug');
        if ($ignoreId) {
            $uniqueSlugRule = $uniqueSlugRule->ignore($ignoreId);
        }

        return $request->validate([
            'name'          => 'required|string|max:255',
            'slug'          => [
                'nullable',
                'string',
                'max:255',
                $uniqueSlugRule,
            ],
            'type'          => ['required', Rule::in(['text', 'select', 'color', 'size', 'checkbox', 'radio'])],
            'is_filterable' => 'boolean',
            'is_required'   => 'boolean',
            'sort_order'    => 'min:0',
        ]);
    }
}
