<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        // Guest => landing
        if (!auth()->check()) {
            return view('landing');
        }

        // Feed (senaste adventures)
        $adventures = Adventure::query()
            ->with(['user'])                 // vem som postat
            ->latest('start_date')           // eller latest() om du har created_at
            ->paginate(10);

        // "Explore foragers" (alla utom mig)
        $foragers = User::query()
            ->whereKeyNot(auth()->id())
            ->orderBy('name')
            ->limit(20)
            ->get();

        return view('home', compact('adventures', 'foragers'));
    }
}
