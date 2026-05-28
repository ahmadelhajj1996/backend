<?php
namespace App\Http\Controllers;

use App\Traits\GeneralTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use GeneralTrait;

    /**
     * Get all notifications for authenticated admin
     */
    public function index(): JsonResponse
    {
        try {

            $admin = auth('admin')->user();

            if (! $admin) {
                return $this->forbiddenResponse('Unauthorized');
            }

            $notifications = $admin->notifications()
                ->latest()
                ->get();

            return $this->successResponse(
                $notifications,
                'Notifications fetched successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to fetch notifications',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Mark all unread notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {

            $admin = auth('admin')->user();

            if (! $admin) {
                return $this->forbiddenResponse('Unauthorized');
            }

            $updated = $admin->unreadNotifications()
                ->update([
                    'read_at' => now(),
                ]);

            return $this->successResponse(
                [
                    'updated_count' => $updated,
                ],
                'Notifications marked as read'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to mark notifications as read',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Mark single notification as read
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {

            $admin = auth('admin')->user();

            if (! $admin) {
                return $this->forbiddenResponse('Unauthorized');
            }

            $notification = $admin->notifications()
                ->where('id', $id)
                ->first();

            if (! $notification) {
                return $this->notFoundResponse('Notification not found');
            }

            $notification->markAsRead();

            return $this->successResponse(
                $notification,
                'Notification marked as read'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to mark notification as read',
                500,
                $e->getMessage()
            );
        }
    }

    /**
     * Delete notification
     */

    public function destroy(string $id): JsonResponse
    {
        try {

            $admin = auth('admin')->user();

            if (! $admin) {
                return $this->forbiddenResponse('Unauthorized');
            }

            $notification = $admin->notifications()
                ->where('id', $id)
                ->first();

            if (! $notification) {
                return $this->notFoundResponse('Notification not found');
            }

            $notification->delete();

            return $this->deletedResponse(
                'Notification deleted successfully'
            );

        } catch (\Exception $e) {

            return $this->errorResponse(
                'Failed to delete notification',
                500,
                $e->getMessage()
            );
        }
    }
}
