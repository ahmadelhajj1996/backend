<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $q = trim($request->q);

        $keywords = preg_split('/\s+/', strtolower($q));

        $products = Product::query()
            ->select(["id", "name", "slug", "featured_image", "category_id"])
            ->with([
                "category:id,name",
                "variations:id,product_id,image,price,sku",
            ])
            ->where("is_active", true)
            ->where("status", "published")
            ->where(function ($query) use ($keywords) {

                foreach ($keywords as $word) {
                    $query->whereRaw('LOWER(name) LIKE ?', ["%{$word}%"]);
                }

            })
            ->limit(8)
            ->get()
            ->map(function ($product) {

                $firstImage = optional(
                    $product->variations->first()?->images->first()
                )->path_url;

                return [
                    "id"       => $product->id,
                    "name"     => $product->name,
                    "slug"     => $product->slug,
                    "category" => $product->category,

                    // ✅ THIS IS THE FIX
                    "image"    => $product->featured_image ?? $firstImage,
                ];
            });

        $categories = Category::query()
            ->select(["id", "name", "slug", "parent_id"])
            ->where("name", "LIKE", "%{$q}%")
            ->orderByRaw("
                            CASE
                                WHEN name LIKE '{$q}%' THEN 1
                                WHEN name LIKE '%{$q}%' THEN 2
                                ELSE 3
                            END
                        ")
            ->limit(5)
            ->get();

        return response()->json([
            "products"   => $products,
            "categories" => $categories,
        ]);
    }
}
