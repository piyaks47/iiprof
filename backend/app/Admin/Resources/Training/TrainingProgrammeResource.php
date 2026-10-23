<?php

namespace App\Admin\Resources\Training;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TrainingProgramme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TrainingProgrammeResource extends Resource
{
    public function key(): string { return 'training-programmes'; }

    public function model(): string { return TrainingProgramme::class; }

    public function label(): string { return 'Training programmes'; }

    public function singular(): string { return 'programme'; }

    public function area(): string { return 'training'; }

    public function icon(): string { return 'academic-cap'; }

    public function description(): ?string { return 'Courses and programmes: cards on the homepage (in tabs by type) and a page each at /training/… with modules, audience, benefits and the scheduled batches.'; }

    public function subtitleFor(Model $record): ?string
    {
        return collect([$record->getAttribute('tagline'), $record->getAttribute('duration'), $record->getAttribute('format')])->filter()->implode(' · ');
    }

    public function badgesFor(Model $record): array
    {
        /** @var TrainingProgramme $record */
        $badges = [[$record->typeLabel(), 'sky']];
        if ($record->is_featured) {
            $badges[] = ['Featured', 'amber'];
        }
        $upcoming = $record->batches()->upcoming()->count();
        $badges[] = [$upcoming.' upcoming '.($upcoming === 1 ? 'batch' : 'batches'), $upcoming ? 'green' : 'slate'];

        return $badges;
    }

    public function filters(): array
    {
        return ['type' => ['label' => 'Type', 'options' => TrainingProgramme::TYPES]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title')->required(),
            Field::text('slug', 'URL slug')->hint('Leave empty to generate from the title. The page is /training/<slug>.')
                ->rules('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('training_programmes', 'slug')->ignore($record?->getKey())),
            Field::select('type')->options(TrainingProgramme::TYPES)->required()->default('open')->hint('The tab it is shown under on the homepage.'),
            Field::text('tagline')->max(255)->hint('Short line on the card, e.g. “For first-time managers”.'),
            Field::textarea('summary')->rows(3)->max(600)->hint('Two or three sentences for the card.'),
            Field::text('duration')->max(120)->hint('e.g. 3 days, 6 weeks, 40 hours.'),
            Field::text('format')->max(120)->hint('e.g. Classroom · Online.'),
            Field::text('fee')->max(120)->hint('e.g. ₹4,500 + GST, Free, On request.'),
            Field::image('image')->hint('Landscape photo, at least 1200 × 800 px.'),
            Field::markdown('description', 'About the programme')->hint('Markdown. Shown on the programme page.'),
            Field::markdown('audience', 'Who should attend')->rows(5),
            Field::markdown('outcomes', 'Programme benefits')->rows(5),
            Field::textarea('modules', 'Course modules')->rows(8)->max(10000)->hint('One module per line.'),
            Field::textarea('certificate')->rows(2)->max(500)->hint('What participants receive at the end.'),
            Field::text('button_text', 'Register button text')->max(80)->default('Register interest'),
            Field::link('button_url', 'Register button link')->default('#enquiry')->hint('#enquiry opens the enquiry form with the programme name.'),
            Field::toggle('is_featured', 'Featured')->hint('Featured programmes are shown first.'),
        ];
    }

    public function publicUrl(Model $record): ?string
    {
        /** @var TrainingProgramme $record */
        return $this->siteUrl($record->path());
    }

    public function emptyText(): string { return 'No programmes yet — the training block is hidden until you add one.'; }
}
