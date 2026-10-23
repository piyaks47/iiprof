<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Seminar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** News & blogs (/news, /news/<slug>) and seminars (/seminars/<slug>). */
class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', '');

        $posts = Post::query()->visible()
            ->when(array_key_exists($type, Post::TYPES), fn ($q) => $q->where('type', $type))
            ->paginate(12);

        return response()->json([
            'data' => collect($posts->items())->map(fn (Post $p) => $p->toSummary())->values(),
            'types' => collect(Post::TYPES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'meta' => ['current_page' => $posts->currentPage(), 'last_page' => $posts->lastPage(), 'total' => $posts->total()],
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::query()->visible()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $post->toDetail() + [
            'related' => Post::query()->visible()->whereKeyNot($post->id)->limit(3)->get()->map(fn (Post $p) => $p->toSummary())->values()->all(),
        ]]);
    }

    public function seminar(string $slug): JsonResponse
    {
        $seminar = Seminar::query()->active()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $seminar->toDetail() + [
            'others' => Seminar::query()->active()->ordered()->whereKeyNot($seminar->id)->limit(3)->get()->map(fn (Seminar $s) => $s->toSummary())->values()->all(),
        ]]);
    }
}
