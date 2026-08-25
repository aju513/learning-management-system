<?php

namespace App\Enums;

enum MaterialType: string
{
    case Article = 'article';
    case Video = 'video';
    case File = 'file';
    case Link = 'link';
    case CourseAssessment = 'course_assessment';

    public function label(): string
    {
        return __(match ($this) {
            self::File => 'File',
            self::Link => 'External link',
            self::Article => 'Article',
            self::Video => 'Video',
            self::CourseAssessment => 'Course assessment',
        });
    }

    public function needsFile(): bool
    {
        return $this === self::File;
    }

    public function supportsFile(): bool
    {
        return in_array($this, [self::Video, self::File], true);
    }
}
