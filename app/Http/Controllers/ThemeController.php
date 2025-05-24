<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Update the user's dark mode preference in session
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function setDarkMode(Request $request)
    {
        // Validate the request
        $request->validate([
            'darkMode' => 'required|in:dark,light',
        ]);

        // Store in session
        $request->session()->put('darkMode', $request->darkMode);

        // Save session for immediate effect
        $request->session()->save();

        return response()->json(['status' => 'success', 'darkMode' => $request->darkMode]);
    }
}
