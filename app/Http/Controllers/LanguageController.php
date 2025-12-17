<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    /**
     * Update user's language preference
     */
    public function update(Request $request)
    {
        $request->validate([
            'language' => 'required|in:pt,en',
        ]);

        $user = $request->user();
        $user->update(['language' => $request->language]);

        App::setLocale($request->language);

        return back()->with('success', __('messages.success'));
    }
}
