<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasImages, Orderable;

    protected $fillable = ['name', 'role', 'quote', 'photo', 'video_url', 'sort_order', 'is_active'];

    public function imageAttributes(): array
    {
        return ['photo'];
    }

    /** Absolute address of a video file (.mp4 / .webm), otherwise null. */
    public function videoFileUrl(): ?string
    {
        $url = (string) $this->video_url;
        if (! preg_match('~\.(mp4|webm)(\?.*)?$~i', $url)) {
            return null;
        }

        return preg_match('~^https?://~i', $url) ? $url : url($url);
    }

    /** https://www.youtube.com/embed/{id} for YouTube links, otherwise null. */
    public function videoEmbedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $this->video_url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        return null;
    }
}
