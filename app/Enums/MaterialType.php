<?php

namespace App\Enums;

enum MaterialType: string
{
    case Article = 'article';
    case Video = 'video';
    case File = 'file';
    case Link = 'link';
    case Assessment = 'assessment';

    public function label(): string
    {
        return match ($this) {
            self::File => 'File',
            self::Link => 'External link',
            self::Assessment => 'Assessment',
            self::Article => 'Article',
            self::Video => 'Video',
        };
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
