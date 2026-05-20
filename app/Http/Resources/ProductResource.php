<?php 

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,

            'name' => $this->name,
            'slug' => $this->slug,

            'description' => $this->description,
            'short_description' => $this->short_description,

            'barcode' => $this->barcode,

            // raw DB value
            'featured_image' => $this->featured_image,

            // 👇 this is what frontend uses
            'featured_image_url' => $this->featured_image_url,

            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_active' => $this->is_active,

            'variations' => $this->variations,
        ];
    }
}