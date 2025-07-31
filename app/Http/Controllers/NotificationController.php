<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    /**
     * Show paginated list of all notifications (read & unread).
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // fetch notifications, paginate 20 per page
        $notifications = $user
            ->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Display a single notification, mark it read if unread.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        // mark it as read
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // you can choose to redirect or show details
        // for simplicity, we'll redirect back to index
        return redirect()->route('notifications.index')
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark a given notification as read via AJAX.
     */
    public function markRead(string $id): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications->each->markAsRead();
        return response()->json(['status'=>'ok']);
    }

    /**
     * Return unread count + latest 5 unread notifications as JSON.
     */
    public function unread(Request $request)
    {
        $user = Auth::user();
        $count  = $user->unreadNotifications()->count();
        $recent = $user->unreadNotifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($n) => [
                'id'      => $n->id,
                'message' => $n->data['message'] ?? '—you have a notification—',
                'time'    => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'count'  => $count,
            'recent' => $recent,
        ]);
    }
}
