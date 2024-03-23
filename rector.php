<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Expression\InlineIfToExplicitIfRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Strict\Rector\Ternary\DisallowedShortTernaryRuleFixerRector;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
    ->withPhpVersion(PhpVersion::PHP_82)
    ->withImportNames(importShortClasses: false)
    ->withPHPStanConfigs([__DIR__ . '/phpstan.dist.neon'])
    ->withCache(__DIR__ . '/build/rector')
    ->withRootFiles()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        instanceOf: true,
        strictBooleans: true,
    )->withSkip([
        CatchExceptionNameMatchingTypeRector::class,
        ClosureToArrowFunctionRector::class,
        DisallowedShortTernaryRuleFixerRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
        InlineIfToExplicitIfRector::class,
    ]);
