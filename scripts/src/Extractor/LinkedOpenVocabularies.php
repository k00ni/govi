<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Graph;
use App\IndexEntry;
use Exception;
use quickRdfIo\Util;

use function App\isEmpty;
use function App\uncompressGzArchive;

/**
 * Read ontology information from Linked Open Vocabularies:
 *
 * https://lov.linkeddata.es/dataset/lov/
 */
class LinkedOpenVocabularies extends AbstractExtractor
{
    protected string $namespace = 'extractor_linked_open_vocabularies';
    protected string $lovN3Filepath = SCRIPTS_DIR_PATH.'var'.DIRECTORY_SEPARATOR.'lov.n3';
    private string $ontologyListUrl = 'https://lov.linkeddata.es/lov.n3.gz';

    /**
     * @throws \Exception
     */
    public function run(): void
    {
        echo PHP_EOL;
        echo '-------------------------------------------------';
        echo PHP_EOL;
        echo 'Linked Open Vocabularies - Extraction started ...';
        echo PHP_EOL;

        $ontologiesToProcess = $this->getOntologiesToProcess();
        echo PHP_EOL;
        echo count($ontologiesToProcess).' ontologies to process';

        // Download latest LOV dump (.gz file) and uncompress it.
        // We assume it contains all vocabulary meta data.
        // TODO if this takes too long, use gunzip for alternatives instead.
        if (file_exists($this->lovN3Filepath)) {
            unlink($this->lovN3Filepath);
        }

        uncompressGzArchive($this->ontologyListUrl, $this->lovN3Filepath);

        echo PHP_EOL;
        echo PHP_EOL;
        echo $this->ontologyListUrl.' downloaded and uncompressed to '.$this->lovN3Filepath;

        // use rapper here, because quickRdfIo doesn't parse lov.n3 here
        $command = 'rapper -i turtle -o ntriples '.$this->lovN3Filepath;
        $nquads = (string) shell_exec($command);
        $iterator = Util::parse($nquads, $this->dataFactory, 'turtle');
        $list = [];
        foreach ($iterator as $quad) {
            $list[] = $quad;
        }
        $graph = new Graph($list);

        foreach ($ontologiesToProcess as $ontology) {
            echo PHP_EOL;
            echo PHP_EOL.' - process '.$ontology->getOntologyTitle().' >> '.$ontology->getOntologyIri();
            $this->addFurtherMetadata($ontology, $graph);

            // get latest N3 file
            $n3Files = [];
            foreach ($graph->getPropertyValues((string) $ontology->getOntologyIri(), 'dcat:distribution') as $value) {
                // related object can be an URI or blank node
                if (str_starts_with($value, '_:')) {
                    // ignore blank nodes
                    echo PHP_EOL.' - ignore blank nodes'.PHP_EOL;
                } else {
                    $n3Files[] = $value;
                }
            }
            rsort($n3Files);

            if (1 > count($n3Files)) {
                echo PHP_EOL.'No N3 files found! Aborting ...';
                continue;
            }

            // we take the first URL to be found
            $ontology->setLatestN3File($n3Files[0]);

            $localFilePath = null;
            $ontologyGraph = new Graph([]);

            // read file to check if there are more meta data available
            if (false == isEmpty($ontology->getLatestN3File()) && null != $ontology->getLatestN3File()) {
                try {
                    $fileHandle = $this->cache->getLocalFileResourceForFileUrl($ontology->getLatestN3File());
                    if (false === is_resource($fileHandle)) {
                        throw new Exception('Could not open related file for '.$ontology->getLatestN3File());
                    }
                } catch (\Throwable $th) {
                    if (str_contains($th->getMessage(), 'HTTP/1.1 404 Not Found')) {
                        echo PHP_EOL;
                        echo PHP_EOL.$ontology->getLatestN3File().' not available: '.$th->getMessage();
                        echo PHP_EOL;
                        continue;
                    } else {
                        throw $th;
                    }
                }

                $localFilePath = $this->cache->getCachedFilePathForFileUrl($ontology->getLatestN3File());
                $ontologyGraph = $this->loadQuadsIntoGraph($fileHandle, $localFilePath, 'n3');
                fclose($fileHandle);

                $this->addFurtherMetadata($ontology, $ontologyGraph);
            }

            if (isEmpty($ontology->getLatestN3File())) {
                throw new Exception('No related dcat:distribution found.');
            }

            if ($this->ontologyFileContainsElementsOfCertainTypes($ontologyGraph)) {
                $this->temporaryIndex->storeEntries([$ontology]);
            } else {
                echo PHP_EOL.' - Aborting, because file '.$localFilePath.' does not contain any ontology related instances';
                echo PHP_EOL;
                continue;
            }
        }
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('Linked Open Vocabularies', $this->ontologyListUrl);
    }

    /**
     * @return array<string,\App\IndexEntry>
     *
     * @throws \Exception
     * @throws \PDOException
     */
    public function getOntologiesToProcess(): array
    {
        $url = 'https://lov.linkeddata.es/dataset/lov/api/v2/vocabulary/list';
        $json = (string) file_get_contents($url);
        $entriesArr = json_decode($json, true);
        $result = [];

        foreach ($entriesArr as $arr) {
            $entry = $this->getPreparedIndexEntry();

            $title = $this->cleanString($arr['titles'][0]['value'], false);
            $entry->setOntologyTitle($title); // TODO: handle case: multiple title entries

            $entry->setOntologyIri($arr['uri']);

            if ($this->temporaryIndex->hasEntry($arr['uri'])) {
                echo PHP_EOL.' - '.$arr['uri'].' already in index, skipping';
                continue;
            }

            $result[(string) $entry->getOntologyIri()] = $entry;
        }

        return $result;
    }
}
