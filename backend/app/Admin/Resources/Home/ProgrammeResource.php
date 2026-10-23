<?php

namespace App\Admin\Resources\Home;

use App\Admin\Field;
use Illuminate\Database\Eloquent\Model;

class ProgrammeResource extends HomeItemResource
{
    public function section(): string { return 'programmes'; }

    public function key(): string { return 'programmes'; }

    public function label(): string { return 'Programmes & initiatives'; }

    public function singular(): string { return 'programme'; }

    public function area(): string { return 'training'; }

    public function icon(): string { return 'academic-cap'; }

    public function description(): ?string { return 'Photo cards for courses, admissions and initiatives on the homepage.'; }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required()->max(160),
            Field::text('subtitle', 'Tag')->max(80)->hint('Short label on the photo, e.g. “Admissions”.'),
            Field::textarea('description')->rows(3)->max(400),
            Field::image('image', 'Photo')->hint('Landscape photo, at least 1200 × 800 px.'),
            Field::text('button_text', 'Link text')->max(80)->default('Learn more'),
            Field::link('button_url', 'Link'),
        ];
    }

    public function emptyText(): string { return 'No programmes yet — the block is hidden until you add one.'; }
}
