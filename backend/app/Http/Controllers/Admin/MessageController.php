<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);
        return response()->json($messages);
    }

    public function markRead(ContactMessage $message)
    {
        $message->markAsRead();
        return response()->json(['message' => 'Marked as read.']);
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();
        return response()->json(['message' => 'Message deleted.']);
    }
}
