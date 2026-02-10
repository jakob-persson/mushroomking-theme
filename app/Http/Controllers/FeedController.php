<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use App\Models\User;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $initialLimit = 5;

        $adventures = Adventure::query()
            ->whereNotNull('start_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->with([
                'user',
                'coverPhoto', // ✅ cover i card
                'photos' => fn ($q) => $q->orderBy('sort')->orderBy('id'),
            ])
            ->take($initialLimit)
            ->get();

        // ✅ RIGHT BAR: Explore foragers (alla utom current user)
        $exploreUsers = User::query()
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->take(20)
            ->get(['id', 'name', 'slug']);

        return view('feed.index', [
            'adventures'     => $adventures,
            'initial_limit'  => $initialLimit,
            'exploreUsers'   => $exploreUsers, // ✅ skicka till view
        ]);
    }
}
