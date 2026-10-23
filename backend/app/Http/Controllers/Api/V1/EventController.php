<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    /** ?when=upcoming (and undated) | past — all events without it. */
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $today = now()->toDateString();
        $when = (string) $request->query('when', '');

        $events = Event::query()->active()
            ->when($when === 'upcoming', fn ($q) => $q->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '>=', $today))->ordered())
            ->when($when === 'past', fn ($q) => $q->whereDate('starts_on', '<', $today)->orderByDesc('starts_on'))
            ->when(! in_array($when, ['upcoming', 'past'], true), fn ($q) => $q->ordered())
            ->get();

        return response()->json(['data' => $events->map(fn (Event $event) => self::summary($event))->values()]);
    }

    public function show(string $slug): JsonResponse
    {
        $event = Event::query()->active()->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => self::summary($event) + [
            'description' => $event->description,
            'description_html' => $event->descriptionHtml(),
        ]]);
    }

    public static function summary(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'slug' => $event->slug,
            'path' => $event->path(),
            'summary' => $event->summary,
            'image_url' => $event->imageUrl(),
            'starts_on' => $event->starts_on?->toDateString(),
            'location' => $event->location,
            'button_text' => $event->button_text,
            'button_url' => $event->button_url,
            'media_url' => $event->media_url,
            'is_past' => $event->starts_on !== null && $event->starts_on->lt(now()->startOfDay()),
        ];
    }
}
