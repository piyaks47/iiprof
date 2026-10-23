<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Http\Resources\PageSummaryResource;
use App\Models\Page;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $pages = Page::query()
            ->published()
            ->orderBy('menu_order')
            ->orderBy('title')
            ->get();

        return PageSummaryResource::collection($pages);
    }

    /** /api/v1/page?path=/industry/banks-nbfc — the page shown at an address of the website. */
    public function byPath(\Illuminate\Http\Request $request): PageResource
    {
        $page = Page::forPath((string) $request->query('path', '/')) ?? abort(404);

        return new PageResource($page);
    }

    public function show(string $slug): PageResource
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return new PageResource($page);
    }
}
