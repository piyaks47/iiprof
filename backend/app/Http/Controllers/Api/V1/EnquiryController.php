<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    /** "Enquire" form on the website. Lands in Admin → Inbox → Enquiries. */
    public function store(Request $request): JsonResponse
    {
        // Honeypot: real visitors never fill the hidden "website" field.
        if (filled($request->input('website'))) {
            return response()->json(['message' => 'Thank you — we will be in touch soon.'], 201);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s.]{6,40}$/'],
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ], ['phone.regex' => 'Please enter a valid phone number.']);

        Enquiry::query()->create($data + ['ip_address' => $request->ip()]);

        return response()->json(['message' => 'Thank you — we will be in touch soon.'], 201);
    }
}
