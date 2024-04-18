<?php

declare(strict_types=1);

namespace App\Command;

use App\Cache;
use App\IndexEntry;
use App\TemporaryIndex;
use Exception;
use PDO;
use rdfInterface\DataFactoryInterface;

/**
 * Manually maintained metadata
 *
 * https://github.com/k00ni/govi/blob/master/manually-maintained-metadata-about-ontologies.csv
 */
class MergeInManuallyMaintainedMetadata
{
    protected Cache $cache;
    private string $csvLink = 'https://github.com/k00ni/govi/blob/master/manually-maintained-metadata-about-ontologies.csv';
    protected DataFactoryInterface $dataFactory;
    protected TemporaryIndex $temporaryIndex;
    protected PDO $temporaryIndexDb;

    public function __construct(Cache $cache, DataFactoryInterface $dataFactory, TemporaryIndex $temporaryIndex)
    {
        $this->cache = $cache;
        $this->dataFactory = $dataFactory;
        $this->temporaryIndex = $temporaryIndex;
    }

    /**
     * merge in all manually maintained metadata which is not already part of the SQLite file
     *
     * @throws \Exception
     * @throws \PDOException
     */
    public function run(): void
    {
        // load CSV file and go through entries ...
        // @phpstan-ignore-next-line
        $entries = array_map('str_getcsv', file(ROOT_DIR_PATH.'/'.MANUALLY_MAINTAINED_METADATA_ABOUT_ONTOLOGIES_CSV));
        foreach ($entries as $line => $row) {
            if (0 == $line) {
                // ignore header
                continue;
            }

            // check if ontology URI is already known
            $entryData = $this->temporaryIndex->getEntryDataAsArray((string) $row[1]);
            if (false === $this->temporaryIndex->hasEntry((string) $row[1])) {
                $entry = $this->getPreparedIndexEntry();
                $entry->setOntologyTitle($row[0]);
                $entry->setOntologyIri($row[1]);

                $entry->setSummary($row[2]);
                $entry->setAuthors($row[3]);
                $entry->setContributors($row[4]);
                $entry->setLicenseInformation($row[5]);
                $entry->setProjectPage($row[6]);
                $entry->setSourcePage($row[7]);

                // related files
                $entry->setLatestJsonLdFile($row[8]);
                $entry->setLatestN3File($row[9]);
                $entry->setLatestNtFile($row[10]);
                $entry->setLatestRdfXmlFile($row[11]);
                $entry->setLatestTurtleFile($row[12]);

                $entry->setModified($row[13]);
                $entry->setVersion($row[14]);

                $this->temporaryIndex->storeEntries([$entry]);
            } elseif (is_array($entryData) && 'Manually maintained' === $entryData['source_title']) {
                echo PHP_EOL.$row[1].' is already in index (manually maintained)';
            } else {
                $msg = 'Ontology '.$row[0].' ('.$row[1].') is known (';
                // @phpstan-ignore-next-line
                $msg .= 'Source: '.$entryData['source_title'];
                $msg .= ') and does not have to be maintained manually!';
                $msg .= ' Please remove it from '.MANUALLY_MAINTAINED_METADATA_ABOUT_ONTOLOGIES_CSV;
                throw new Exception($msg);
            }
        }
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('Manually maintained', $this->csvLink);
    }
}
