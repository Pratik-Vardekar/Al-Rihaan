<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactSetting;

class ContactController extends Controller
{
    public function contactDetails()
    {
        $contact = ContactSetting::first();
        return response()->json([
            'status' => true,
            'data' => $contact
        ]);
    }
}

