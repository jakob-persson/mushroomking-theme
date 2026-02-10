<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(User $user)
    {
        return view('profile.show', compact('user'));
    }

    public function updateModal(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'country'      => ['nullable', 'string', 'max:255'],
            'presentation' => ['nullable', 'string', 'max:2000'],
        ]);

        $user->name = $data['display_name'];
        $user->email = $data['email'];
        $user->country = $data['country'] ?: null;

        // OBS: kräver kolumnen `presentation` i users-tabellen
        $user->presentation = $data['presentation'] ?: null;

        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $user->country,
                'presentation' => $user->presentation,
            ],
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        // Radera gammal avatar (valfritt men nice)
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        // Spara ny fil i storage/app/public/avatars/...
        $path = $request->file('avatar')->store('avatars', 'public');

        // OBS: kräver kolumnen `avatar_path` i users-tabellen
        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'success' => true,
            'url' => Storage::url($path), // => /storage/avatars/...
        ]);
    }
}
