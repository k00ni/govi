<?php

declare(strict_types=1);

use App\Cache;
use App\Extractor\DBpediaArchivo;
use App\Extractor\LinkedOpenVocabularies;
use quickRdf\DataFactory;

/**
 * Executes all extractors.
 */

require 'bootstrap.php';

global $cache;
$cache = new Cache();
$dataFactory = new DataFactory();

(new LinkedOpenVocabularies($cache, $dataFactory))->run();
// (new DBpediaArchivo($cache, $dataFactory))->run();

echo PHP_EOL;
echo PHP_EOL;
