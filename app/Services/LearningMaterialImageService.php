<?php

namespace App\Services;

use App\Models\CourseChapter;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialImage;
use App\Models\User;
use App\Repositories\Contracts\LearningMaterialImageRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LearningMaterialImageService
{
    private const MAX_SIZE = 5 * 1024 * 1024;

    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __construct(private readonly LearningMaterialImageRepositoryInterface $images) {}

    public function upload(CourseChapter $chapter, UploadedFile $file, User $actor, ?LearningMaterial $material = null): LearningMaterialImage
    {
        $uuid = (string) Str::uuid();
        $extension = strtolower($file->extension() ?: 'bin');
        $path = "lms/content-images/{$uuid}.{$extension}";

        Storage::disk('local')->putFileAs('lms/content-images', $file, "{$uuid}.{$extension}");

        try {
            $image = $this->images->create([
                'uuid' => $uuid,
                'course_chapter_id' => $chapter->id,
                'learning_material_id' => null,
                'uploaded_by' => $actor->id,
                'disk' => 'local',
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'original_filename' => $file->getClientOriginalName(),
                'size' => (int) $file->getSize(),
                'expires_at' => now()->addDay(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        activity('lms')->causedBy($actor)->performedOn($image)->event('learning-material-image.uploaded')
            ->withProperties(['chapter_id' => $chapter->id, 'material_id' => $material?->id])->log('Learning material image uploaded');

        return $image;
    }

    public function sanitizeContent(?string $html, CourseChapter $chapter, User $actor, ?LearningMaterial $material = null): ?string
    {
        if (! filled($html)) {
            return $html;
        }

        $referenced = $this->imagesReferencedBy($html);
        $images = $this->authorizedImages($referenced, $chapter, $actor, $material);
        $routes = $images->keyBy('uuid');
        $allowed = '<p><br><strong><b><em><i><ul><ol><li><h2><h3><blockquote><img>';
        $clean = strip_tags($html, $allowed);

        return (string) preg_replace_callback('/<\/?([a-z][a-z0-9]*)\b([^>]*)>/i', function (array $match) use ($routes): string {
            $tag = strtolower($match[1]);
            $closing = str_starts_with($match[0], '</');

            if (! in_array($tag, ['p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'h2', 'h3', 'blockquote', 'img'], true)) {
                return '';
            }
            if ($closing) {
                return $tag === 'img' ? '' : "</{$tag}>";
            }
            if ($tag !== 'img') {
                return "<{$tag}>";
            }

            preg_match('/\bsrc\s*=\s*["\']([^"\']+)["\']/i', $match[2], $srcMatch);
            $uuid = $this->uuidFromUrl($srcMatch[1] ?? '');
            if (! $uuid || ! $routes->has($uuid)) {
                return '';
            }

            preg_match('/\balt\s*=\s*["\']([^"\']*)["\']/i', $match[2], $altMatch);
            $alt = htmlspecialchars(strip_tags($altMatch[1] ?? ''), ENT_QUOTES, 'UTF-8');

            return '<img src="'.e(route('learning-material-images.show', $routes->get($uuid))).'" alt="'.$alt.'">';
        }, $clean);
    }

    public function synchronize(LearningMaterial $material, ?string $content, CourseChapter $chapter, User $actor): Collection
    {
        $referenced = $this->imagesReferencedBy($content ?? '');
        $images = $this->authorizedImages($referenced, $chapter, $actor, $material);
        $current = $this->images->forMaterial($material);
        $removed = $current->whereNotIn('uuid', $images->pluck('uuid'))->values();

        foreach ($removed as $image) {
            $this->images->delete($image);
        }

        $pending = $images->whereNull('learning_material_id');
        $this->images->attachToMaterial($pending, $material);

        return $removed;
    }

    public function deleteForMaterial(LearningMaterial $material): Collection
    {
        $images = $this->images->forMaterial($material);
        foreach ($images as $image) {
            $this->images->delete($image);
        }

        return $images;
    }

    public function deletePendingForChapter(CourseChapter $chapter): Collection
    {
        $images = $this->images->pendingForChapter($chapter);
        foreach ($images as $image) {
            $this->images->delete($image);
        }

        return $images;
    }

    public function deleteFiles(Collection $images): void
    {
        foreach ($images as $image) {
            Storage::disk($image->disk)->delete($image->path);
        }
    }

    public function cleanupExpired(): int
    {
        $images = $this->images->expiredPending();
        $this->deleteFiles($images);
        foreach ($images as $image) {
            $this->images->delete($image);
        }

        return $images->count();
    }

    public function response(LearningMaterialImage $image): Response
    {
        if (! Storage::disk($image->disk)->exists($image->path)) {
            throw new NotFoundHttpException;
        }

        return Storage::disk($image->disk)->response($image->path, $image->original_filename, [
            'Content-Type' => $image->mime_type,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public static function allowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }

    public static function maxSize(): int
    {
        return self::MAX_SIZE;
    }

    private function authorizedImages(array $uuids, CourseChapter $chapter, User $actor, ?LearningMaterial $material): Collection
    {
        if ($uuids === []) {
            return collect();
        }

        $images = collect($uuids)->map(fn (string $uuid) => $this->images->findByUuid($uuid));
        if ($images->contains(fn (?LearningMaterialImage $image): bool => ! $image)) {
            throw ValidationException::withMessages(['content' => 'One or more embedded images are unavailable.']);
        }

        foreach ($images as $image) {
            $belongsToMaterial = $material && (int) $image->learning_material_id === (int) $material->id;
            $isPendingUpload = $image->learning_material_id === null
                && (int) $image->course_chapter_id === (int) $chapter->id
                && (int) $image->uploaded_by === (int) $actor->id;
            if (! $belongsToMaterial && ! $isPendingUpload) {
                throw ValidationException::withMessages(['content' => 'One or more embedded images are not available to this material.']);
            }
        }

        return $images;
    }

    private function imagesReferencedBy(string $html): array
    {
        preg_match_all('/<img\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        return collect($matches[1] ?? [])
            ->map(fn (string $url): ?string => $this->uuidFromUrl($url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function uuidFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        preg_match('#/learning-material-images/([0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12})$#i', $path, $matches);

        return isset($matches[1]) ? strtolower($matches[1]) : null;
    }
}
