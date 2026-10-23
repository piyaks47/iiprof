<?php

namespace App\Admin\Resources;

use App\Admin\Field;
use App\Admin\Resource;
use App\Models\GalleryItem;
use Illuminate\Database\Eloquent\Model;

class GalleryItemResource extends Resource
{
    public function key(): string { return 'gallery'; }

    public function model(): string { return GalleryItem::class; }

    public function label(): string { return 'Gallery'; }

    public function singular(): string { return 'photo'; }

    public function area(): string { return 'gallery'; }

    public function icon(): string { return 'photo'; }

    public function titleFor(Model $record): string
    {
        return (string) ($record->getAttribute('title') ?: 'Photo #'.$record->getKey());
    }

    public function fields(?Model $record): array
    {
        return [
            Field::text('title', 'Caption'),
            Field::image('image', 'Photo')->required()->hint('JPG, PNG or WebP, up to 5 MB.'),
        ];
    }

    public function emptyText(): string { return 'No photos yet — the gallery section stays hidden until you add one.'; }
}
