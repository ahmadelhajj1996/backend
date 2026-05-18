<?php

namespace App\Http\Controllers;

use App\Models\Characteristic;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CharacteristicController extends Controller
{
    /**
     * List all characteristics
     */
    public function index()
    {
        $characteristics = Characteristic::with('variation')
            ->latest()
            ->paginate(15);

        return $this->successResponse(
            $characteristics,
            'Characteristics retrieved successfully'
        );
    }

    /**
     * Store new characteristic
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'variation_id' => 'required|exists:variations,id',
                'attribute'    => 'required|string|max:255',
            ]);

            $characteristic = Characteristic::create($validated);

            return $this->createdResponse(
                $characteristic->load('variation'),
                'Characteristic created successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    /**
     * Show single characteristic
     */
    public function show($id)
    {
        $characteristic = Characteristic::with('variation')->find($id);

        if (! $characteristic) {
            return $this->notFoundResponse('Characteristic not found');
        }

        return $this->successResponse(
            $characteristic,
            'Characteristic retrieved successfully'
        );
    }

    /**
     * Update characteristic
     */
    public function update(Request $request, $id)
    {
        try {
            $characteristic = Characteristic::find($id);

            if (! $characteristic) {
                return $this->notFoundResponse('Characteristic not found');
            }

            $validated = $request->validate([
                'variation_id' => 'sometimes|required|exists:variations,id',
                'attribute'    => 'sometimes|required|string|max:255',
            ]);

            $characteristic->update($validated);

            return $this->updatedResponse(
                $characteristic->load('variation'),
                'Characteristic updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    /**
     * Delete characteristic
     */
    public function destroy($id)
    {
        $characteristic = Characteristic::find($id);

        if (! $characteristic) {
            return $this->notFoundResponse('Characteristic not found');
        }

        $characteristic->delete();

        return $this->deletedResponse(
            'Characteristic deleted successfully'
        );
    }
}