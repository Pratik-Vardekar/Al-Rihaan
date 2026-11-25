<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayFunSection;
use Illuminate\Http\Request;

class PlayFunSectionController extends Controller
{
    public function edit()
    {
        $section = PlayFunSection::firstOrCreate([]);
        return view('admin.play-fun-section.edit', compact('section'));
    }

    public function update(Request $request)
    {
        $section = PlayFunSection::firstOrCreate([]);

        $data = $request->only([
            'tagline',
            'headline',
            'button_text',
            'button_url',
            'stats',
        ]);

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('backend/assets/images'), $imageName);
            $data['image'] = $imageName;
        }

        $section->update($data);

        return back()->with('success', 'Play Fun Section updated successfully.');
    }
}

