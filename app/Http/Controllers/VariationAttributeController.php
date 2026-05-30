<?php

namespace App\Http\Controllers;

use App\Models\VariationAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\RecalculateVariationPriceJob;

class VariationAttributeController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = VariationAttribute::with([
                'variation',
                'attribute',
                'option.attribute',
            ]);

            if ($request->filled('variation_id')) {
                $query->where('variation_id', $request->variation_id);
            }

            if ($request->filled('attribute_id')) {
                $query->where('attribute_id', $request->attribute_id);
            }

            if ($request->filled('attribute_option_id')) {
                $query->where('attribute_option_id', $request->attribute_option_id);
            }

            if ($request->filled('search')) {

                $search = $request->search;

                $query->where(function ($q) use ($search) {

                    $q->whereHas('variation', fn ($vq) =>
                        $vq->where('sku', 'like', "%{$search}%")
                    )
                    ->orWhereHas('option', fn ($oq) =>
                        $oq->where('name', 'like', "%{$search}%")
                    )
                    ->orWhereHas('attribute', fn ($aq) =>
                        $aq->where('name', 'like', "%{$search}%")
                    );
                });
            }

            $sortField = $request->get('sort_by', 'id');
            $sortOrder = $request->get('sort_order', 'asc');

            $allowed = [
                'id',
                'variation_id',
                'attribute_id',
                'attribute_option_id',
                'created_at',
            ];

            if (!in_array($sortField, $allowed)) {
                $sortField = 'id';
            }

            return $this->successResponse(
                $query->orderBy($sortField, $sortOrder)
                      ->paginate($request->get('per_page', 15)),
                'Variation attributes retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {

            $data = VariationAttribute::with([
                'variation',
                'attribute',
                'option.attribute',
            ])->find($id);

            if (!$data) {
                return $this->notFoundResponse('Not found');
            }

            return $this->successResponse($data);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {

            $attr = VariationAttribute::find($id);

            if (!$attr) {
                return $this->notFoundResponse('Not found');
            }

            DB::transaction(fn () => $attr->delete());

            return $this->deletedResponse('Deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getByVariation($variationId)
    {
        try {

            return $this->successResponse(
                VariationAttribute::where('variation_id', $variationId)
                    ->with(['attribute', 'option.attribute'])
                    ->get()
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {

            $validated = $this->validateData($request);

            $attr = DB::transaction(fn () =>
                VariationAttribute::create([
                    'variation_id' => $validated['variation_id'],
                    'attribute_id' => $validated['attribute_id'],
                    'attribute_option_id' => $validated['attribute_option_id'],
                    'price_override' => $validated['price_override'] ?? null,
                    'is_price_override' => isset($validated['price_override']),
                ])
            );

            RecalculateVariationPriceJob::dispatch($attr->variation_id);

            return $this->createdResponse($attr->load(['variation', 'attribute', 'option.attribute']));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $attr = VariationAttribute::findOrFail($id);
            $validated = $this->validateData($request);

            DB::transaction(fn () =>
                $attr->update([
                    'variation_id' => $validated['variation_id'],
                    'attribute_id' => $validated['attribute_id'],
                    'attribute_option_id' => $validated['attribute_option_id'],
                    'price_override' => $validated['price_override'] ?? null,
                    'is_price_override' => isset($validated['price_override']),
                ])
            );

            RecalculateVariationPriceJob::dispatch($attr->variation_id);

            return $this->updatedResponse($attr->fresh()->load(['variation', 'attribute', 'option.attribute']));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function validateData(Request $request)
    {
        return $request->validate([
            'variation_id' => ['required', 'exists:variations,id'],
            'attribute_id' => ['required', 'exists:attributes,id'],
            'attribute_option_id' => ['required', 'exists:attribute_options,id'],
            'price_override' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}