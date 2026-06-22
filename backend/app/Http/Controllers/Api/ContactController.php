<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Models\ContactMessage;

class ContactController extends Controller
{
    public function store(ContactFormRequest $request)
    {
        ContactMessage::create($request->validated());

        return response()->json([
            'message' => 'Your message has been received. I will get back to you as soon as possible.'
        ]);
    }
}
