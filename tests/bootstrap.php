<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

define('APP_ROOT', realpath(path: dirname(path: __DIR__)));
const APP_CONFIG = 'temp_config';

require_once __DIR__ . '/src/AbstractTestCase.php';

// required test helpers, so we include them manually.
// Note: helpers live under Trait/Helper/ for historical reasons (originally
// helpers for ReflectionTrait, deleted in Beta 1). They are now consumed by
// the Service tests (AttributeReader/ReflectionInspector).
require_once __DIR__ . '/src/Trait/Helper/DummyAttribute.php';
require_once __DIR__ . '/src/Trait/Helper/DummyClassWithAttribute.php';
require_once __DIR__ . '/src/Trait/Helper/FinalReadOnlyClass.php';
require_once __DIR__ . '/src/Trait/Helper/NonFinalTestController.php';
