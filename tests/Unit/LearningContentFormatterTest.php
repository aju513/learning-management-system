<?php

use App\Support\LearningContentFormatter;

test('legacy bullet and numbered paragraphs become semantic lists', function () {
    $html = '<p>Overview</p><p>• First point</p><p>• Second point</p><p>1. First step</p><p>2. Second step</p>';

    expect(LearningContentFormatter::toSemanticLists($html))
        ->toContain('<ul><li>First point</li><li>Second point</li></ul>')
        ->toContain('<ol><li>First step</li><li>Second step</li></ol>')
        ->not->toContain('<p>• First point</p>');
});
