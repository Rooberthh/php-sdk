<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withImportNames(removeUnusedImports: true)
    ->withSkip([
        // Pint's phpdoc_add_missing_param_annotation re-adds these, so the two would fight.
        RemoveUselessParamTagRector::class,
    ]);
