<?php

namespace App\Enums;

enum MaterialType: string
{
    case Article = 'article';
    case Video = 'video';
    case Pdf = 'pdf';
    case Ppt = 'ppt';
    case Pptx = 'pptx';
    case Doc = 'doc';
    case Docx = 'docx';
    case ExternalLink = 'external_link';
    case DownloadableFile = 'downloadable_file';
    case Assessment = 'assessment';

    public function label(): string
    {
        return match ($this) {
            self::ExternalLink => 'External link',
            self::DownloadableFile => 'Downloadable file',
            self::Assessment => 'Quiz / test',
            default => strtoupper($this->value),
        };
    }

    public function needsFile(): bool
    {
        return in_array($this, [self::Pdf, self::Ppt, self::Pptx, self::Doc, self::Docx, self::DownloadableFile], true);
    }

    public function supportsFile(): bool
    {
        return $this === self::Video || $this->needsFile();
    }
}
