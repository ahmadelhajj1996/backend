<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{

    public function index(): JsonResponse
    {
        $categories = Category::with(['parent', 'children'])->get();

        return $this->successResponse(
            $categories,
            'Categories retrieved successfully'
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|unique:categories,slug|max:255',
            'description' => 'nullable|string',
            'parent_id'   => [
                'nullable',
                'exists:categories,id',
                'required_if:type,sub',
            ],
            'type'        => 'nullable|string|max:100',
            'image'       => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'is_active'   => 'boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($request->hasFile('image')) {
            $validated['image'] = ImageHelper::upload(
                $request->file('image'),
                'categories'
            );
        }

        $category = Category::create($validated);
        $category->load(['parent', 'children']);

        return $this->createdResponse(
            $category,
            'Category created successfully'
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);

            $validated = $request->validate([
                'name'         => 'sometimes|required|string|max:255',
                'slug'         => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                    Rule::unique('categories', 'slug')->ignore($category->id),
                ],
                'description'  => 'nullable|string',
                'parent_id'    => [
                    'nullable',
                    'exists:categories,id',
                    'required_if:type,sub',
                    function ($attribute, $value, $fail) use ($category) {
                        if ($value == $category->id) {
                            $fail('Category cannot be its own parent.');
                        }
                    },
                ],
                'type'         => 'nullable|string|max:100',
                'image'        => 'nullable|string|max:255',
                'image'        => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
                'remove_image' => 'nullable|boolean',
                'is_active'    => 'boolean',
            ]);

            if (isset($validated['name']) && empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
            }

            if ($request->boolean('remove_image')) {
                ImageHelper::delete($category->image);
                $validated['image'] = null;
            }

            if ($request->hasFile('image')) {

                $newImage = ImageHelper::upload(
                    $request->file('image'),
                    'categories'
                );

                ImageHelper::delete($category->image);

                $validated['image'] = $newImage;
            }

            $category->update($validated);
            $category->load(['parent', 'children']);

            return $this->updatedResponse(
                $category,
                'Category updated successfully'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Category not found');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $category      = Category::findOrFail($id);
            $childrenCount = $category->children()->count();

            if ($category->type === 'main' && $childrenCount > 0) {
                return $this->cannotDeleteWithChildrenResponse($childrenCount, 'main');
            }

            if ($childrenCount > 0) {
                return $this->cannotDeleteWithChildrenResponse($childrenCount);
            }
            if ($category->image) {
                ImageHelper::delete($category->image);
            }
            $category->delete();

            return $this->deletedResponse('Category deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse('Category not found');
        }
    }

}
