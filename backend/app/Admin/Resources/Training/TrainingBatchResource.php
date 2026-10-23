<?php

namespace App\Admin\Resources\Training;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\TrainingBatch;
use App\Models\TrainingProgramme;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TrainingBatchResource extends Resource
{
    public function key(): string { return 'training-batches'; }

    public function model(): string { return TrainingBatch::class; }

    public function label(): string { return 'Training batches'; }

    public function singular(): string { return 'batch'; }

    public function area(): string { return 'training'; }

    public function icon(): string { return 'calendar'; }

    public function sortable(): bool { return false; }

    public function description(): ?string { return 'Scheduled dates of the programmes (“Scheduled dates” on the homepage, the training calendar and each programme page). Finished batches disappear from the website by themselves.'; }

    public function query(): Builder
    {
        return TrainingBatch::query()->with('programme:id,title,slug')
            ->orderByRaw('CASE WHEN COALESCE(ends_on, starts_on) >= ? THEN 0 ELSE 1 END', [now()->toDateString()])
            ->orderBy('starts_on')->orderBy('id');
    }

    public function titleFor(Model $record): string
    {
        /** @var TrainingBatch $record */
        return trim(($record->programme?->title ?? 'Programme').($record->batch_code ? ' · '.$record->batch_code : ''));
    }

    public function subtitleFor(Model $record): ?string
    {
        /** @var TrainingBatch $record */
        return collect([$record->dateRange(), $record->formatLabel(), $record->location, $record->schedule])->filter()->implode(' · ');
    }

    public function badgesFor(Model $record): array
    {
        /** @var TrainingBatch $record */
        return $record->isUpcoming() ? [['Upcoming', 'green']] : [['Finished', 'slate']];
    }

    public function imageFor(Model $record): ?string { return null; }

    public function filters(): array
    {
        return ['training_programme_id' => ['label' => 'Programme', 'options' => TrainingProgramme::query()->ordered()->pluck('title', 'id')->mapWithKeys(fn ($t, $id) => [(string) $id => $t])->all()]];
    }

    public function fields(?Model $record): array
    {
        return [
            Field::select('training_programme_id', 'Programme')->options(fn () => TrainingProgramme::query()->ordered()->pluck('title', 'id')->all(), '— Choose —')->required(),
            Field::text('batch_code', 'Batch no.')->max(60)->hint('Optional, e.g. HUN-24.'),
            Field::select('format')->options(TrainingBatch::FORMATS)->required()->default('classroom'),
            Field::text('location')->max(160)->hint('City or venue; e.g. Kolkata, or Online.'),
            Field::date('starts_on', 'First day')->required(),
            Field::date('ends_on', 'Last day')->hint('Leave empty for a one-day batch.'),
            Field::text('schedule')->max(255)->hint('e.g. Sat & Sun, 10 am – 1 pm.'),
            Field::number('seats')->hint('Optional; 0 = not shown.'),
            Field::link('register_url', 'Registration link')->hint('Leave empty to open the enquiry form with the programme and dates.'),
        ];
    }

    public function beforeSave(Model $record, array $data): void
    {
        /** @var TrainingBatch $record */
        if ($record->ends_on && $record->starts_on && $record->ends_on->lt($record->starts_on)) {
            throw ValidationException::withMessages(['data.ends_on' => 'The last day cannot be before the first day.']);
        }
        if ($record->seats === 0) {
            $record->seats = null;
        }
    }

    public function emptyText(): string { return 'No batches yet — “Scheduled dates” is hidden until you add one.'; }
}
