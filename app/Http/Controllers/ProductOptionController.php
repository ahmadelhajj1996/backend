<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\AttributeOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class ProductOptionController extends Controller
{
    /**
     * Display a paginated list of product options.
     */
    public function index(Request $request)
    {
        try {
            $query = ProductOption::query();

            // // Filter by product_id
            // if ($request->has('product_id')) {
            //     $query->where('product_id', $request->product_id);
            // }

            // // Filter by attribute_option_id
            // if ($request->has('attribute_option_id')) {
            //     $query->where('attribute_option_id', $request->attribute_option_id);
            // }

            // // Include relationships if requested
            // if ($request->has('with_product') && $request->boolean('with_product')) {
            //     $query->with('product');
            // }
            // if ($request->has('with_attribute_option') && $request->boolean('with_attribute_option')) {
            //     $query->with('attributeOption');
            // }

            // // Default: include both relationships for convenience
            // if (!$request->has('with_product') && !$request->has('with_attribute_option')) {
            //     $query->with(['product', 'attributeOption']);
            // }

            $perPage = $request->get('per_page', 15);
            $productOptions = $query->paginate($perPage);

            return $this->successResponse($productOptions, 'Product options retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product options: ' . $e->getMessage(), 500);
        }
    }

     public function store(Request $request)
    {
        try {
            $validated = $this->validateProductOption($request);

            // Ensure product and attribute option exist
            $product = Product::find($validated['product_id']);
            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $attributeOption = AttributeOption::find($validated['attribute_option_id']);
            if (!$attributeOption) {
                return $this->notFoundResponse('Attribute option not found');
            }

            $productOption = DB::transaction(function () use ($validated) {
                return ProductOption::create($validated);
            });

            $productOption->load(['product', 'attributeOption']);

            return $this->createdResponse($productOption, 'Product option created successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified product option.
     */
    public function show($id)
    {
        try {
            $productOption = ProductOption::with(['product', 'attributeOption'])->find($id);

            if (!$productOption) {
                return $this->notFoundResponse('Product option not found');
            }

            return $this->successResponse($productOption, 'Product option retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product option: ' . $e->getMessage(), 500);
        }
    }

 
    public function update(Request $request, $id)
    {
        try {
            $productOption = ProductOption::find($id);

            if (!$productOption) {
                return $this->notFoundResponse('Product option not found');
            }

            $validated = $this->validateProductOption($request, $productOption->id);

            // Validate related models exist if they are being changed
            if (isset($validated['product_id'])) {
                $product = Product::find($validated['product_id']);
                if (!$product) {
                    return $this->notFoundResponse('Product not found');
                }
            }

            if (isset($validated['attribute_option_id'])) {
                $attributeOption = AttributeOption::find($validated['attribute_option_id']);
                if (!$attributeOption) {
                    return $this->notFoundResponse('Attribute option not found');
                }
            }

            $updatedProductOption = DB::transaction(function () use ($productOption, $validated) {
                $productOption->update($validated);
                return $productOption->fresh();
            });

            $updatedProductOption->load(['product', 'attributeOption']);

            return $this->updatedResponse($updatedProductOption, 'Product option updated successfully');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified product option.
     */
    public function destroy($id)
    {
        try {
            $productOption = ProductOption::find($id);

            if (!$productOption) {
                return $this->notFoundResponse('Product option not found');
            }

            DB::transaction(function () use ($productOption) {
                $productOption->delete();
            });

            return $this->deletedResponse('Product option deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Restore a soft-deleted product option.
     * Requires SoftDeletes trait in model and migration.
     */
    public function restore($id)
    {
        try {
            $productOption = ProductOption::withTrashed()->find($id);

            if (!$productOption) {
                return $this->notFoundResponse('Product option not found');
            }

            if (!$productOption->trashed()) {
                return $this->errorResponse('Product option is not deleted', 400);
            }

            DB::transaction(function () use ($productOption) {
                $productOption->restore();
            });

            return $this->successResponse($productOption->fresh(), 'Product option restored successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore product option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Permanently delete a soft-deleted product option.
     * Requires SoftDeletes trait in model and migration.
     */
    public function forceDelete($id)
    {
        try {
            $productOption = ProductOption::withTrashed()->find($id);

            if (!$productOption) {
                return $this->notFoundResponse('Product option not found');
            }

            DB::transaction(function () use ($productOption) {
                $productOption->forceDelete();
            });

            return $this->deletedResponse('Product option permanently deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to permanently delete product option: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all product options for a specific product.
     */
    public function getByProduct($productId)
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $options = ProductOption::where('product_id', $productId)
                ->with('attributeOption')
                ->get();

            return $this->successResponse($options, 'Product options retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product options: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove all product options for a given product (bulk delete).
     */
    public function deleteByProduct($productId)
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            $deleted = DB::transaction(function () use ($productId) {
                return ProductOption::where('product_id', $productId)->delete();
            });

            return $this->successResponse(['deleted_count' => $deleted], 'Product options deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product options: ' . $e->getMessage(), 500);
        }
    }


    
    
    private function validateProductOption(Request $request, $ignoreId = null)
    {
        $uniqueRule = Rule::unique('product_options', 'product_id')
            ->where('attribute_option_id', $request->attribute_option_id);

        if ($ignoreId) {
            $uniqueRule = $uniqueRule->ignore($ignoreId);
        }

        return $request->validate([
            'product_id' => 'required|exists:products,id',
            'attribute_option_id' => 'required|exists:attribute_options,id',
            $uniqueRule
        ]);
    }
}