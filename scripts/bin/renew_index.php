<?php

declare(strict_types=1);

use App\Cache;
use App\Command\MergeInManuallyMaintainedMetadata;
use App\Extractor\BioPortal;
use App\Extractor\DBpediaArchivo;
use App\Extractor\LinkedOpenVocabularies;
use App\Extractor\OntologyLookupService;
use App\TemporaryIndex;
use quickRdf\DataFactory;

require 'bootstrap.php';

global $cache;
$cache = new Cache();
$dataFactory = new DataFactory();
$temporaryIndex = new TemporaryIndex();

// run extractors to fill temporary_index.db
(new DBpediaArchivo($cache, $dataFactory, $temporaryIndex))->run();
(new LinkedOpenVocabularies($cache, $dataFactory, $temporaryIndex))->run();
(new OntologyLookupService($cache, $dataFactory, $temporaryIndex))->run();
(new BioPortal($cache, $dataFactory, $temporaryIndex))->run();

// finalize temporary index and write index.csv
(new MergeInManuallyMaintainedMetadata($cache, $dataFactory, $temporaryIndex))->run();

$temporaryIndex->writeToIndexCsv();

echo PHP_EOL;
echo PHP_EOL;
