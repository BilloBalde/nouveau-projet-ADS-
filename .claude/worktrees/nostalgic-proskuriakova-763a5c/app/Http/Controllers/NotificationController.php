<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gestion des notifications in-app (staff et clients — chaque user voit les siennes).
 */
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Flux JSON pollé par la cloche (badge + dropdown).
     */
    public function feed()
    {
        $user = Auth::user();

        return response()->json([
            'unread_count'  => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($n) => [
                    'id'      => $n->id,
                    'message' => $n->data['message'] ?? '',
                    'url'     => $n->data['url'] ?? null,
                    'read'    => $n->read_at !== null,
                    'time'    => $n->created_at->diffForHumans(),
                ]),
        ]);
    }

    public function markRead(string $id)
    {
        $notification = Auth::user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true, 'url' => $notification->data['url'] ?? null]);
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }
}
