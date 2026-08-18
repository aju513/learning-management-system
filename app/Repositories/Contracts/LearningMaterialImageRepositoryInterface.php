<?php

namespace App\Repositories\Contracts;

use App\Models\CourseChapter;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialImage;
use App\Models\User;
use Illuminate\Support\Collection;

interface LearningMaterialImageRepositoryInterface
{
    public function create(array $attributes): LearningMaterialImage;

    public function findByUuid(string $uuid): ?LearningMaterialImage;

    public function pendingFor(User $user, CourseChapter $chapter, array $uuids): Collection;

    public function forMaterial(LearningMaterial $material): Collection;

    public function pendingForChapter(CourseChapter $chapter): Collection;

    public function attachToMaterial(Collection $images, LearningMaterial $material): void;

    public function delete(LearningMaterialImage $image): void;

    public function expiredPending(): Collection;
}
