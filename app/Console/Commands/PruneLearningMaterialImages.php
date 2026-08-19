<?php

namespace App\Console\Commands;

use App\Services\LearningMaterialImageService;
use Illuminate\Console\Command;

class PruneLearningMaterialImages extends Command
{
    protected $signature = 'lms:prune-material-images';

    protected $description = 'Remove expired learning-material image uploads';

    public function handle(LearningMaterialImageService $images): int
    {
        $count = $images->cleanupExpired();
        $this->info("Removed {$count} expired learning-material image(s).");

        return self::SUCCESS;
    }
}
