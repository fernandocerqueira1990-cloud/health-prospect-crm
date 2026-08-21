<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        /** @var DatabaseNotification $item */
        $item = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $item->markAsRead();

        $url = $item->data['url'] ?? null;

        return is_string($url) && $url !== ''
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('status', 'Notificações marcadas como lidas.');
    }
}
