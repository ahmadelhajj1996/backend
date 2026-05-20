<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait GeneralTrait
{
  
    protected function successResponse($data = null, string $message = 'Operation successful', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

   
    protected function errorResponse(string $message, int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

   
    protected function createdResponse($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

  
    protected function updatedResponse($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->successResponse($data, $message, 200);
    }
 
    protected function deletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->successResponse(null, $message, 200);
    }
 
    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

   
    protected function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }
 
    protected function cannotDeleteWithChildrenResponse(int $childrenCount, string $type = 'category'): JsonResponse
    {
        $message = $type === 'main' 
            ? "Cannot delete main {$type} with subcategories. Please delete or reassign all child {$type}s first."
            : "Cannot delete {$type} with subcategories. Please delete or reassign children first.";
            
        return response()->json([
            'success' => false,
            'message' => $message,
            'children_count' => $childrenCount,
        ], 422);
    }
 
    protected function forbiddenResponse(string $message = 'Action forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }
}