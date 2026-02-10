<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileEditController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'display_name' => ['required','string','max:60'],
            'email'        => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'country'      => ['nullable','string','max:80'],
            'presentation' => ['nullable','string','max:1000'],
        ]);

        $user->name = $data['display_name'];
        $user->email = $data['email'];
        $user->country = $data['country'] ?? null;
        $user->presentation = $data['presentation'] ?? null;
        $user->save();

        return back()->with('status', 'Saved');
    }

    public function avatar(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => ['required','image','mimes:jpg,jpeg,png,webp','max:4096'],
        ]);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'url' => Storage::url($path),
        ]);
    }
}
