<?php

namespace App\Repositories\Eloquent;

use App\Models\CourseChapter;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialImage;
use App\Models\User;
use App\Repositories\Contracts\LearningMaterialImageRepositoryInterface;
use Illuminate\Support\Collection;

class LearningMaterialImageRepository implements LearningMaterialImageRepositoryInterface
{
    public function create(array $attributes): LearningMaterialImage
    {
        return LearningMaterialImage::query()->create($attributes);
    }

    public function findByUuid(string $uuid): ?LearningMaterialImage
    {
        return LearningMaterialImage::query()
            ->with(['chapter.module.course', 'material.chapter.module.course'])
            ->where('uuid', $uuid)
            ->first();
    }

    public function pendingFor(User $user, CourseChapter $chapter, array $uuids): Collection
    {
        return LearningMaterialImage::query()
            ->whereNull('learning_material_id')
            ->where('course_chapter_id', $chapter->id)
            ->where('uploaded_by', $user->id)
            ->whereIn('uuid', $uuids)
            ->get();
    }

    public function forMaterial(LearningMaterial $material): Collection
    {
        return $material->images()->get();
    }

    public function pendingForChapter(CourseChapter $chapter): Collection
    {
        return LearningMaterialImage::query()
            ->where('course_chapter_id', $chapter->id)
            ->whereNull('learning_material_id')
            ->get();
    }

    public function attachToMaterial(Collection $images, LearningMaterial $material): void
    {
        if ($images->isEmpty()) {
            return;
        }

        LearningMaterialImage::query()
            ->whereIn('id', $images->pluck('id')->all())
            ->update([
                'learning_material_id' => $material->id,
                'expires_at' => null,
            ]);
    }

    public function delete(LearningMaterialImage $image): void
    {
        $image->delete();
    }

    public function expiredPending(): Collection
    {
        return LearningMaterialImage::query()
            ->whereNull('learning_material_id')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }
}
