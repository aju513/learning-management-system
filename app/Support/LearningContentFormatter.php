<?php

namespace App\Support;

final class LearningContentFormatter
{
    /**
     * Convert legacy paragraph-based list content to semantic HTML lists.
     */
    public static function toSemanticLists(?string $html): string
    {
        if (! filled($html)) {
            return '';
        }

        $html = self::replaceListBlocks(
            $html,
            '/(?:(?:<p>\s*(?:[•◦▪‣]|[-*])\s*.*?<\/p>\s*)+)/isu',
            'ul',
            '/<p>\s*(?:[•◦▪‣]|[-*])\s*(.*?)\s*<\/p>/isu',
        );

        return self::replaceListBlocks(
            $html,
            '/(?:(?:<p>\s*\d+[.)]\s*.*?<\/p>\s*)+)/isu',
            'ol',
            '/<p>\s*\d+[.)]\s*(.*?)\s*<\/p>/isu',
        );
    }

    private static function replaceListBlocks(string $html, string $blockPattern, string $listTag, string $itemPattern): string
    {
        return (string) preg_replace_callback($blockPattern, function (array $matches) use ($listTag, $itemPattern): string {
            preg_match_all($itemPattern, $matches[0], $items);

            if (empty($items[1])) {
                return $matches[0];
            }

            $listItems = collect($items[1])
                ->map(fn (string $item): string => '<li>'.$item.'</li>')
                ->implode('');

            return "<{$listTag}>{$listItems}</{$listTag}>";
        }, $html);
    }
}
