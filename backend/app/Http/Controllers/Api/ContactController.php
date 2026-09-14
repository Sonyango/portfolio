<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactFormRequest;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContactController extends Controller
{
    public function store(ContactFormRequest $request)
    {
        // Hoenypot check. If 'website' field is filled, it's a bot
        if ($request->input('website')) {
            // Return fake success to fool the bot
            return response()->json([
                'message' => 'Your message has been received!'
            ]);
        }

        $validated = $request->validated();

        // Rate limiting: Allow only 3 messages per hour per IP(stricter than the middleware)
        $ip = $request->ip();
        $cacheKey = 'contact_form_' . md5($ip);
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= 3) {
            return response()->json([
                'message' => 'Too many messages from this IP address. Please try again later.'
            ], 429);
        }

        Cache::put($cacheKey, $attempts + 1, now()->addHours(1));

        // Rate limiting per email
        $emailKey = 'contact_email_' . md5($validated['email']);
        $emailAttempts = Cache::get($emailKey, 0);

        if ($emailAttempts >= 2) {
            return response()->json([
                'message' => 'This email has already submitted a message recently.'
            ], 429);
        }

        Cache::put($emailKey, $emailAttempts + 1, now()->addHours(24));

        // Sanitize inputs before saving
        $sanitized = [
            'name' => strip_tags($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'subject' => strip_tags($validated['subject']),
            'message' => strip_tags($validated['message']),
        ];


        ContactMessage::create($sanitized);

        return response()->json([
            'message' => 'Your message has been received. I will get back to you as soon as possible.'
        ]);
    }
}
