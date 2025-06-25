<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Menampilkan daftar semua notifikasi untuk user yang login.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(15);

        return view('pages.notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        // Log untuk debugging
        Log::info('markAsRead called', [
            'notification_id' => $notification->id,
            'notification_notifiable_id' => $notification->notifiable_id,
            'current_user_id' => Auth::id(),
            'request_method' => $request->method(),
        ]);

        // Cek authorization
        if ($notification->notifiable_id !== Auth::id()) {
            Log::warning('Unauthorized attempt to mark notification as read.', [
                'attempted_notification_id' => $notification->id,
                'notification_owner_id' => $notification->notifiable_id,
                'current_user_id' => Auth::id()
            ]);
            return response()->json(['success' => false, 'message' => 'Notifikasi ini bukan milik Anda.'], 403);
        }

        // Mark as read
        $notification->markAsRead();

        // Get redirect link
        $redirectLink = $notification->data['link'] ?? '#';

        if (!filter_var($redirectLink, FILTER_VALIDATE_URL)) {
            Log::error('Invalid redirect link found in notification data:', [
                'notification_id' => $notification->id,
                'invalid_link' => $redirectLink
            ]);
            $redirectLink = route('redirect-dashboard');
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai sudah dibaca.',
            'unread_count' => Auth::user()->unreadNotifications->count(),
            'link_to_redirect' => $redirectLink
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $request->user()->unreadNotifications->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca',
                'unread_count' => 0
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking all notifications as read:', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
