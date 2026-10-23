<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TrainingBatch;
use App\Models\TrainingProgramme;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/** Training programmes and their scheduled batches (/training and /training/<slug> on the website). */
class TrainingController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'types' => collect(TrainingProgramme::TYPES)->map(fn ($label, $type) => ['key' => $type, 'label' => $label])->values(),
            'programmes' => self::programmes(),
            'batches' => TrainingBatch::query()->upcoming()->with('programme')->whereHas('programme', fn ($q) => $q->where('is_active', true))
                ->get()->map(fn (TrainingBatch $b) => $b->toApi())->values(),
        ]]);
    }

    public function show(string $slug): JsonResponse
    {
        $programme = TrainingProgramme::query()->where('is_active', true)->where('slug', $slug)->firstOrFail();

        return response()->json(['data' => $programme->toDetail() + [
            'related' => TrainingProgramme::query()->where('is_active', true)->whereKeyNot($programme->id)
                ->where('type', $programme->type)->ordered()->limit(3)->get()
                ->map(fn (TrainingProgramme $p) => $p->toSummary())->values()->all(),
        ]]);
    }

    /** Active programmes, featured first, each with its next batch. */
    public static function programmes(): Collection
    {
        return TrainingProgramme::query()->where('is_active', true)
            ->with(['batches' => fn ($q) => $q->upcoming()])
            ->orderByDesc('is_featured')->ordered()->get()
            ->map(fn (TrainingProgramme $p) => $p->toSummary())->values();
    }
}
