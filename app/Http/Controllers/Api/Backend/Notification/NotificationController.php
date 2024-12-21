<?php

namespace App\Http\Controllers\Api\Backend\Notification;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

/**
 * Contains api endpoint functions for admin related tasks
 *
 * Require ROLE_ADMIN for *every* controller method in this class.
 *
 */
class NotificationController extends Controller
{

    public function __construct(

    ) {
        parent::__construct();
    }

    public function index(Request $request)
    {
        return $this->sendSuccessResponse(
            "Notifications Response.",
            $request->user()->notifications()->paginate(
                $request->query->getInt('per_page', 10),
                ['*'],
                'page',
                $request->query->getInt('page', 1)
            )
        );
    }

    public function edit(Notification $notification, Request $request)
    {
        return $this->sendSuccessResponse(
            "Notifications Response.",
            $notification
        );
    }

    public function destroy(Notification $notification, Request $request)
    {
        $request->user()->notifications()->where('id', $notification->id)->delete($notification->id);
        return $this->sendSuccessResponse(
            "Deleted.",
            []
        );
    }

    public function deleteAll(Request $request)
    {
        $request->user()->notifications()->delete();
        return $this->sendSuccessResponse(
            "Deleted all.",
            []
        );
    }


    public function markAsRead(Notification $notification, Request $request)
    {
        $request->user()->notifications()->where('id', $notification->id)->first()?->markAsRead($notification->id);
        return $this->sendSuccessResponse(
            "Marked as read.",
            []
        );
    }

    public function markAsUnread(Notification $notification, Request $request)
    {
        $request->user()->notifications()->where('id', $notification->id)->first()?->markAsUnread($notification->id);
        return $this->sendSuccessResponse(
            "Marked as unread.",
            []
        );
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->notifications->markAsRead();
        return $this->sendSuccessResponse(
            "Marked all as read.",
            []
        );
    }

    public function markAllAsUnread(Request $request)
    {
        $request->user()->notifications->markAsUnread();
        return $this->sendSuccessResponse(
            "Marked all as unread.",
            []
        );
    }

    public function getUnreadCount(Request $request)
    {

        return $this->sendSuccessResponse(
            "Unread count.",
            $request->user()->unreadNotifications()->count()
        );
    }

    public function getReadCount(Request $request)
    {
        return $this->sendSuccessResponse(
            "Read count.",
            $request->user()->readNotifications()->count()
        );
    }


}
