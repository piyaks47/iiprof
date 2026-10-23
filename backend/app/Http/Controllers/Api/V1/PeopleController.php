<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\GalleryItem;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Management pages, testimonials and the full photo gallery. */
class PeopleController extends Controller
{
    public function team(Request $request): JsonResponse
    {
        $group = (string) $request->query('group', '');

        $members = TeamMember::query()->active()
            ->when($group !== '', fn ($q) => $q->where('group', $group))
            ->ordered()->get()
            ->map(fn (TeamMember $m) => [
                'id' => $m->id, 'group' => $m->group, 'name' => $m->name, 'role' => $m->role,
                'bio' => $m->bio, 'bio_html' => $m->bio ? (string) \Illuminate\Support\Str::markdown($m->bio, ['html_input' => 'strip', 'allow_unsafe_links' => false]) : '',
                'photo_url' => $m->imageUrl('photo'),
            ]);

        return response()->json(['data' => [
            'groups' => collect(TeamMember::GROUPS)->map(fn ($title, $key) => ['key' => $key, 'title' => $title, 'path' => '/management/'.$key])->values(),
            'members' => $members->values(),
        ]]);
    }

    public function testimonials(): JsonResponse
    {
        return response()->json(['data' => Testimonial::query()->active()->ordered()->get()->map(fn (Testimonial $t) => self::testimonial($t))->values()]);
    }

    /** @return array<string, mixed> */
    public static function testimonial(Testimonial $t): array
    {
        return [
            'id' => $t->id, 'name' => $t->name, 'role' => $t->role, 'quote' => $t->quote,
            'photo_url' => $t->imageUrl('photo'), 'video_url' => $t->video_url, 'video_embed_url' => $t->videoEmbedUrl(), 'video_file_url' => $t->videoFileUrl(),
        ];
    }

    public function gallery(): JsonResponse
    {
        return response()->json(['data' => GalleryItem::query()->active()->ordered()->get()->map(fn (GalleryItem $g) => [
            'id' => $g->id, 'title' => $g->title, 'image_url' => $g->imageUrl(),
        ])->values()]);
    }
}
