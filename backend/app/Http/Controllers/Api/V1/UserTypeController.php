<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\JsonResponse;

class UserTypeController extends Controller
{
    /** Active user types with their active subtypes — for sign-up forms (web and React Native). */
    public function __invoke(): JsonResponse
    {
        $types = UserType::query()->active()->ordered()
            ->with(['subtypes' => fn ($q) => $q->where('is_active', true)])
            ->get()
            ->map(fn (UserType $type) => [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'description' => $type->description,
                'subtypes' => $type->subtypes->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'slug' => $s->slug])->values(),
            ]);

        return response()->json(['data' => $types]);
    }
}
