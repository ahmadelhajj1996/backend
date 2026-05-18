<?php
namespace App\Http\Controllers;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        try {

            $query = Product::with([
                'variations.images',
                'variations.attributes.option.attribute',
                'category',
                'variations.characteristics',
            ]);

            // Filters

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->filled('is_active')) {
                $query->where(
                    'is_active',
                    $request->boolean('is_active')
                );
            }

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            // Sorting

            $sortField = $request->get(
                'sort_by',
                'created_at'
            );

            $sortOrder = $request->get(
                'sort_order',
                'desc'
            );

            $allowedSortFields = [
                'id',
                'name',
                'price',
                'created_at',
                'updated_at',
                'view_count',
                'sold_count',
            ];

            if (in_array($sortField, $allowedSortFields)) {
                $query->orderBy($sortField, $sortOrder);
            }

            $perPage = $request->get('per_page', 15);

            $products = $query->paginate($perPage);

            return $this->successResponse(
                $products,
                'Products retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve products',
                500
            );
        }
    }

    public function featured()
    {
        try {

            $products = Product::with([
                'category:id,name',
                'variations.images',
                ''

            ])
                ->where('is_featured', true)
                ->where('is_active', true)
                ->latest()
                ->paginate(15);

            return $this->successResponse(
                $products,
                'Featured products retrieved successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to retrieve featured products: ' . $e->getMessage(),
                500
            );
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'category_id'       => 'required|exists:categories,id',
                'name'              => 'required|string|max:255',
                'slug'              => 'nullable|string|unique:products,slug|max:255',
                'description'       => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'sku'               => 'nullable|string|unique:products,sku|max:100',
                'barcode'           => 'nullable|string|unique:products,barcode|max:100',
                'weight'            => 'nullable|numeric|min:0|max:999999.99',
                'featured_image'    => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
                'status'            => ['required', Rule::in(['draft', 'published', 'archived'])],
                'is_featured'       => 'boolean',
                'is_active'         => 'boolean',
            ]);

            // Generate slug if not provided
            if (empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
                // Ensure uniqueness
                $originalSlug = $validated['slug'];
                $count        = 1;
                while (Product::where('slug', $validated['slug'])->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $count++;
                }
            }

            // Encode gallery array to JSON
            if (isset($validated['gallery'])) {
                $validated['gallery'] = json_encode($validated['gallery']);
            }

            if ($request->hasFile('featured_image')) {
                $validated['featured_image'] = ImageHelper::upload(
                    $request->file('featured_image'),
                    'products'
                );
            }

            $product = DB::transaction(function () use ($validated) {
                return Product::create($validated);
            });

            return $this->createdResponse($product->load('category'), 'Product created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with([
                'variations.images',
                'variations.attributes.option.attribute',
                'variations.characteristics',
            ])->find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            $product->increment('view_count');

            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product', 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $product = Product::find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            $validated = $request->validate([
                'category_id'           => 'sometimes|required|exists:categories,id',
                'name'                  => 'sometimes|required|string|max:255',
                'slug'                  => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('products', 'slug')->ignore($product->id),
                ],
                'description'           => 'nullable|string',
                'short_description'     => 'nullable|string|max:500',
                'sku'                   => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('products', 'sku')->ignore($product->id),
                ],
                'barcode'               => [
                    'nullable',
                    'string',
                    'max:100',
                    Rule::unique('products', 'barcode')->ignore($product->id),
                ],
                'featured_image'        => [
                    'nullable',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:2048',
                ],
                'remove_featured_image' => 'nullable|boolean',
                'status'                => ['sometimes', 'required', Rule::in(['draft', 'published', 'archived'])],
                'is_featured'           => 'boolean',
                'is_active'             => 'boolean',
            ]);

            if (isset($validated['name']) && empty($validated['slug'])) {
                $validated['slug'] = Str::slug($validated['name']);
                // Ensure uniqueness
                $originalSlug = $validated['slug'];
                $count        = 1;
                while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $count++;
                }
            }

            // Remove image
            if ($request->boolean('remove_featured_image')) {
                ImageHelper::delete($product->featured_image);
                $validated['featured_image'] = null;
            }

            if ($request->hasFile('featured_image')) {

                $newImage = ImageHelper::upload(
                    $request->file('featured_image'),
                    'products'
                );

                ImageHelper::delete($product->featured_image);

                $validated['featured_image'] = $newImage;
            }

            $updatedProduct = DB::transaction(function () use ($product, $validated) {
                $product->update($validated);
                return $product->fresh();
            });

            return $this->updatedResponse($updatedProduct->load('category'), 'Product updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $product = Product::find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            DB::transaction(function () use ($product) {
                if ($product->featured_image) {
                    ImageHelper::delete($variation->featured_image);
                }
                $product->delete();
            });

            return $this->deletedResponse('Product deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product', 500);
        }
    }

    public function restore($id)
    {
        try {
            $product = Product::withTrashed()->find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            if (! $product->trashed()) {
                return $this->errorResponse('Product is not deleted', 400);
            }

            DB::transaction(function () use ($product) {
                $product->restore();
            });

            return $this->successResponse($product->load('category'), 'Product restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore product', 500);
        }
    }

    public function forceDelete($id)
    {
        try {
            $product = Product::withTrashed()->find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            DB::transaction(function () use ($product) {
                // Delete associated images from storage if needed
                if ($product->featured_image) {
                    Storage::disk('public')->delete($product->featured_image);
                }

                if ($product->gallery) {
                    $galleryImages = json_decode($product->gallery, true) ?? [];
                    foreach ($galleryImages as $image) {
                        Storage::disk('public')->delete($image);
                    }
                }

                $product->forceDelete();
            });

            return $this->deletedResponse('Product permanently deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to permanently delete product', 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            ]);

            $product = Product::find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            $product->update(['status' => $validated['status']]);

            return $this->updatedResponse($product, 'Product status updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product status', 500);
        }
    }

    public function updateStock(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'quantity'  => 'required|integer|min:0',
                'operation' => ['nullable', Rule::in(['set', 'add', 'subtract'])],
            ]);

            $product = Product::find($id);

            if (! $product) {
                return $this->notFoundResponse('Product not found');
            }

            $operation = $validated['operation'] ?? 'set';

            switch ($operation) {
                case 'add':
                    $newQuantity = $product->quantity + $validated['quantity'];
                    break;
                case 'subtract':
                    $newQuantity = max(0, $product->quantity - $validated['quantity']);
                    break;
                default:
                    $newQuantity = $validated['quantity'];
            }

            $product->update(['quantity' => $newQuantity]);

            return $this->updatedResponse($product, 'Product stock updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product stock', 500);
        }
    }

}
