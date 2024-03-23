<?php

declare(strict_types=1);

return [
    'commands' => [],
    'configDir' => __DIR__ . '/build/psysh/config',
    'dataDir' => __DIR__ . '/build/psysh/data',
    'defaultIncludes' => [],
    'eraseDuplicates' => true,
    'errorLoggingLevel' => \E_ALL,
    'forceArrayIndexes' => true,
    'historySize' => 1000,
    'runtimeDir' => __DIR__ . '/build/psysh/tmp',
    'updateCheck' => 'never',
    'useBracketedPaste' => true,
    'verbosity' => \Psy\Configuration::VERBOSITY_NORMAL,
];
