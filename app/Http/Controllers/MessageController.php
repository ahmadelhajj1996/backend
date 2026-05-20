<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
{
    /**
     * List all messages
     */
    public function index()
    {
        $messages = Message::latest()
            ->paginate(15);

        return $this->successResponse(
            $messages,
            'Messages retrieved successfully'
        );
    }

    /**
     * Store new message
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:5000',
            ]);

            $message = Message::create($validated);

            return $this->createdResponse(
                $message,
                'Message created successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    /**
     * Show single message
     */
    public function show($id)
    {
        $message = Message::find($id);

        if (! $message) {
            return $this->notFoundResponse(
                'Message not found'
            );
        }

        return $this->successResponse(
            $message,
            'Message retrieved successfully'
        );
    }

    /**
     * Update message
     */
    public function update(Request $request, $id)
    {
        try {
            $message = Message::find($id);

            if (! $message) {
                return $this->notFoundResponse(
                    'Message not found'
                );
            }

            $validated = $request->validate([
                'content' => 'sometimes|required|string|max:5000',
            ]);

            $message->update($validated);

            return $this->updatedResponse(
                $message,
                'Message updated successfully'
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse(
                $e->errors(),
                'Validation failed'
            );
        }
    }

    /**
     * Delete message
     */
    public function destroy($id)
    {
        $message = Message::find($id);

        if (! $message) {
            return $this->notFoundResponse(
                'Message not found'
            );
        }

        $message->delete();

        return $this->deletedResponse(
            'Message deleted successfully'
        );
    }

    /**
     * Restore soft deleted message
     */
    public function restore($id)
    {
        $message = Message::withTrashed()->find($id);

        if (! $message) {
            return $this->notFoundResponse(
                'Message not found'
            );
        }

        if (! $message->trashed()) {
            return $this->errorResponse(
                'Message is not deleted',
                400
            );
        }

        $message->restore();

        return $this->successResponse(
            $message,
            'Message restored successfully'
        );
    }

    /**
     * Permanently delete message
     */
    public function forceDelete($id)
    {
        $message = Message::withTrashed()->find($id);

        if (! $message) {
            return $this->notFoundResponse(
                'Message not found'
            );
        }

        $message->forceDelete();

        return $this->deletedResponse(
            'Message permanently deleted successfully'
        );
    }
}