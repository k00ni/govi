<?php

declare(strict_types=1);

use App\Cache;
use App\Command\MergeInManuallyMaintainedMetadata;
use App\Extractor\BioPortal;
use App\Extractor\DBpediaArchivo;
use App\Extractor\LinkedOpenVocabularies;
use App\Extractor\OntologyLookupService;
use App\Extractor\SweetOntologies;
use App\TemporaryIndex;
use quickRdf\DataFactory;

require 'bootstrap.php';

global $cache;
$cache = new Cache();
$dataFactory = new DataFactory();
$temporaryIndex = new TemporaryIndex();

// run extractors to fill temporary_index.db
foreach ([
    DBpediaArchivo::class,
    LinkedOpenVocabularies::class,
    OntologyLookupService::class,
    BioPortal::class,
    SweetOntologies::class,
] as $class) {
    /** @var \App\Extractor\AbstractExtractor */
    $extractor = new $class($cache, $dataFactory, $temporaryIndex);
    $extractor->run();
    $extractor = null;
}

// finalize temporary index and write index.csv
(new MergeInManuallyMaintainedMetadata($cache, $dataFactory, $temporaryIndex))->run();

$temporaryIndex->writeToIndexCsv();

echo PHP_EOL;
echo PHP_EOL;
