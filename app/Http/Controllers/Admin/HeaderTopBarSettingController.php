<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeaderTopBarSetting;
use Illuminate\Http\Request;

class HeaderTopBarSettingController extends Controller
{
    public function edit()
    {
        $setting = HeaderTopBarSetting::firstOrCreate([], [
            'title1' => 'Get free home delivery',
            'title2' => 'Get free home delivery',
        ]);

        return view('admin.header-top-bar-settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'title1' => 'nullable|string|max:255',
            'title2' => 'nullable|string|max:255'
        ]);

        $setting = HeaderTopBarSetting::firstOrCreate([]);

        $setting->update($request->only([
            'title1',
            'title2'
        ]));

        return back()->with('success', 'Header top bar settings updated successfully.');
    }
}
