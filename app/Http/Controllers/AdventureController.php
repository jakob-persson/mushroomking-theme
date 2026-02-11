<?php

namespace App\Http\Controllers;

use App\Models\Adventure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class AdventureController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'location'       => ['nullable', 'string', 'max:120'],
            'start_date'     => ['nullable', 'date'],
            'adventure_text' => ['nullable', 'string', 'max:5000'],
            'types'          => ['required', 'string'],

            'photos'         => ['nullable', 'array', 'max:10'],
            'photos.*'       => ['file', 'image', 'max:24576'], // ✅ 24MB
        ]);

        $types = json_decode($validated['types'] ?? '', true);
        if (!is_array($types) || empty($types)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mushroom types payload.',
            ], 422);
        }

        try {
            $adventure = DB::transaction(function () use ($user, $validated, $types, $request) {
                $adventure = Adventure::create([
                    'user_id'        => $user->id,
                    'location'       => $validated['location'] ?? null,
                    'start_date'     => $validated['start_date'] ?? null,
                    'adventure_text' => $validated['adventure_text'] ?? null,
                    'types'          => $types,
                ]);

                $files = $request->file('photos', []);
                foreach (array_values((array) $files) as $i => $file) {
                    if (!$file || !$file->isValid()) continue;

                    $saved = $this->storeVariants($file, $adventure->id);

                    $adventure->photos()->create([
                        'path'      => $saved['path'],       // ✅ large ratio
                        'feed_path' => $saved['feed_path'],  // ✅ 1080x1080
                        'sort'      => $i,
                    ]);
                }

                return $adventure;
            });

            return $this->adventureResponse($adventure, 201);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => app()->isLocal()
                    ? ($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine())
                    : 'Could not save adventure.',
            ], 500);
        }
    }

    // ✅ Edit / Update adventure
    public function update(Request $request, Adventure $adventure)
    {
        $user = $request->user();

        if ($adventure->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Not allowed.',
            ], 403);
        }

        $validated = $request->validate([
            'location'       => ['nullable', 'string', 'max:120'],
            'start_date'     => ['nullable', 'date'],
            'adventure_text' => ['nullable', 'string', 'max:5000'],
            'types'          => ['required', 'string'],

            // nya uploads (valfritt)
            'photos'         => ['nullable', 'array', 'max:10'],
            'photos.*'       => ['file', 'image', 'max:24576'], // ✅ 24MB

            // ids på bilder som användaren tog bort i edit (JSON array)
            'remove_photo_ids' => ['nullable', 'string'],
        ]);

        $types = json_decode($validated['types'] ?? '', true);
        if (!is_array($types) || empty($types)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid mushroom types payload.',
            ], 422);
        }

        $removeIds = json_decode($validated['remove_photo_ids'] ?? '[]', true);
        if (!is_array($removeIds)) $removeIds = [];

        try {
            DB::transaction(function () use ($adventure, $validated, $types, $request, $removeIds) {

                // uppdatera fälten
                $adventure->update([
                    'location'       => $validated['location'] ?? null,
                    'start_date'     => $validated['start_date'] ?? null,
                    'adventure_text' => $validated['adventure_text'] ?? null,
                    'types'          => $types,
                ]);

                // ta bort valda befintliga foton (DB + filer)
                if (!empty($removeIds)) {
                    $photosToDelete = $adventure->photos()->whereIn('id', $removeIds)->get();

                    foreach ($photosToDelete as $p) {
                        if ($p->path) {
                            Storage::disk('public')->delete($p->path);
                        }
                        if ($p->feed_path) {
                            Storage::disk('public')->delete($p->feed_path);
                        }
                    }

                    $adventure->photos()->whereIn('id', $removeIds)->delete();
                }

                // lägg till nya uploads sist
                $existingCount = $adventure->photos()->count();
                $files = $request->file('photos', []);

                foreach (array_values((array) $files) as $i => $file) {
                    if (!$file || !$file->isValid()) continue;

                    $saved = $this->storeVariants($file, $adventure->id);

                    $adventure->photos()->create([
                        'path'      => $saved['path'],
                        'feed_path' => $saved['feed_path'],
                        'sort'      => $existingCount + $i,
                    ]);
                }

                // re-index sort (0..n)
                $all = $adventure->photos()->orderBy('sort')->orderBy('id')->get();
                foreach ($all as $idx => $p) {
                    if ((int) $p->sort !== $idx) {
                        $p->sort = $idx;
                        $p->save();
                    }
                }
            });

            return $this->adventureResponse($adventure, 200);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => app()->isLocal()
                    ? ($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine())
                    : 'Could not update adventure.',
            ], 500);
        }
    }

    // ✅ Delete adventure (dropdown)
    public function destroy(Request $request, Adventure $adventure)
    {
        $user = $request->user();

        if ($adventure->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Not allowed.',
            ], 403);
        }

        try {
            $adventure->load('photos');

            foreach ($adventure->photos as $p) {
                if ($p->path) {
                    Storage::disk('public')->delete($p->path);
                }
                if ($p->feed_path) {
                    Storage::disk('public')->delete($p->feed_path);
                }
            }

            $adventure->photos()->delete();
            $adventure->delete();

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => app()->isLocal()
                    ? ($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine())
                    : 'Could not delete adventure.',
            ], 500);
        }
    }

    // ---- Helper: bygger samma JSON payload för create/update ----
    private function adventureResponse(Adventure $adventure, int $status)
    {
        $adventure->load(['user', 'photos']);
        $photos = collect($adventure->photos ?? [])->sortBy([
            fn ($a, $b) => ($a->sort <=> $b->sort) ?: ($a->id <=> $b->id),
        ])->values();

        return response()->json([
            'success' => true,
            'adventure' => [
                'id'             => $adventure->id,
                'location'       => $adventure->location,
                'start_date'     => optional($adventure->start_date)->toDateString(),
                'adventure_text' => $adventure->adventure_text,
                'types'          => $adventure->types,
                'user' => [
                    'id'   => $adventure->user?->id,
                    'name' => $adventure->user?->name,
                    'slug' => $adventure->user?->slug,
                ],
                'photos' => $photos->map(fn ($p) => [
                    'id'       => $p->id,
                    'url'      => $p->path ? asset('storage/' . $p->path) : null,         // ✅ modal ratio
                    'feed_url' => $p->feed_path ? asset('storage/' . $p->feed_path) : null, // ✅ feed 1080x1080
                    'sort'     => $p->sort,
                ])->values(),
                'cover' => $photos->first() && $photos->first()->feed_path
                    ? asset('storage/' . $photos->first()->feed_path) // ✅ feed cover
                    : null,
            ],
        ], $status);
    }

    // ---- Helper: sparar webp-varianter (modal ratio + feed 1080x1080) ----
   private function storeVariants($file, int $adventureId): array
{
    $quality = 82;
    $largeMax = 2400;
    $feedSize = 1080;

    $img = Image::read($file->getPathname());

    $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9\-_]+/', '-', $base);
    $base = trim($base, '-') ?: 'photo';

    $name = $base . '-' . uniqid() . '.webp';
    $dir = "adventures/{$adventureId}";

    $largePath = "{$dir}/{$name}";
    $feedPath  = "{$dir}/feed-{$name}";

    // ✅ PHP clone istället för ->clone()
    $large = (clone $img)->scaleDown(width: $largeMax, height: $largeMax);
    Storage::disk('public')->put($largePath, (string) $large->toWebp($quality));

    $feed = (clone $img)->cover($feedSize, $feedSize);
    Storage::disk('public')->put($feedPath, (string) $feed->toWebp($quality));

    return [
        'path' => $largePath,
        'feed_path' => $feedPath,
    ];
}

}
