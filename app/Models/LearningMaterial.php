<?php

namespace App\Models;

use App\Enums\MaterialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LearningMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_chapter_id', 'title', 'type', 'description', 'content', 'video_url', 'external_url', 'file_type',
        'file_path', 'original_filename', 'mime_type', 'duration_minutes', 'position', 'is_required',
    ];

    protected function casts(): array
    {
        return ['type' => MaterialType::class, 'is_required' => 'boolean'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(CourseChapter::class, 'course_chapter_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }

    public function courseAssessment(): HasOne
    {
        return $this->hasOne(CourseAssessment::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LearningMaterialImage::class);
    }

    public function youtubeEmbedUrl(): ?string
    {
        if (! $this->video_url) {
            return null;
        }

        $host = strtolower((string) parse_url($this->video_url, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        $path = (string) parse_url($this->video_url, PHP_URL_PATH);
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = trim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'm.youtube.com'], true)) {
            parse_str((string) parse_url($this->video_url, PHP_URL_QUERY), $query);
            $videoId = $query['v'] ?? (preg_match('#^/(?:embed|shorts|live)/([^/?]+)#', $path, $matches) ? $matches[1] : null);
        }

        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.rawurlencode($videoId);
    }
}
