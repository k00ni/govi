<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Cache;
use App\IndexEntry;
use App\TemporaryIndex;
use DateTime;
use EasyRdf\Format;
use Exception;
use rdfInterface\DataFactoryInterface;

use function App\isEmpty;

/**
 * Read ontology information from BioPortals API:
 *
 * https://data.bioontology.org/documentation
 * https://data.bioontology.org/ontologies?include=all&display_context=false&display_links=true&apikey=XXX
 */
class BioPortal extends AbstractExtractor
{
    /**
     * @var non-empty-string
     */
    protected string $apiKey;
    protected string $namespace = 'extractor_bioportal';
    private string $ontologyListUrl = 'https://data.bioontology.org/ontologies?include=all&display_context=false&display_links=true&apikey=';

    /**
     * @throws \Error
     * @throws \Exception if no API key file was found
     */
    public function __construct(Cache $cache, DataFactoryInterface $dataFactory, TemporaryIndex $temporaryIndex)
    {
        parent::__construct($cache, $dataFactory, $temporaryIndex);

        if (false === file_exists(BIOPORTAL_API_KEY_FILE)) {
            throw new Exception('No API key file found. Please rename '.BIOPORTAL_API_KEY_FILE.' and insert your personal API key');
        }

        $this->apiKey = require BIOPORTAL_API_KEY_FILE;
    }

    /**
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function run(): void
    {
        echo PHP_EOL;
        echo '-------------------------------------------------';
        echo PHP_EOL;
        echo 'BioPortal - Extraction started ...';
        echo PHP_EOL;

        foreach ($this->getOntologiesToProcess() as $ontology) {
            $newEntry = $this->getPreparedIndexEntry();

            // title
            $newEntry->setOntologyTitle($ontology['name']);

            echo PHP_EOL;
            echo '---------------------------------------------------------------------';
            echo PHP_EOL;
            echo 'Next: '.$newEntry->getOntologyTitle();

            // URI
            // example: https://github.com/ncbo/ontologies_api/issues/138#issuecomment-2035077045
            $url = $ontology['links']['latest_submission'].'?include=all&display_context=false&display_links=false&apikey=';
            $url .= $this->apiKey;
            $content = $this->cache->sendCachedRequest($url, $this->namespace);
            $arr = json_decode($content, true);
            if (isEmpty($arr['uri'] ?? null)) {
                echo PHP_EOL.' - IGNORED because latest submission is empty > '.$url.PHP_EOL;
                continue;
            } else {
                $newEntry->setOntologyIri($arr['uri']);
            }

            if ($this->temporaryIndex->hasEntry((string) $newEntry->getOntologyIri())) {
                echo PHP_EOL.'- entry already in temp. index, skipping';
                continue;
            }

            // link UI page
            $newEntry->setSourcePage($ontology['links']['ui']);

            // get related RDF data
            $uiContent = $this->cache->sendCachedRequest($ontology['links']['ui'], $this->namespace);
            $regex = "/href='(https:\/\/data\.bioontology\.org\/ontologies\/[a-zA-Z\-_]+\/download)\?apikey=.*?&(download_format=rdf)/smi";
            preg_match($regex, $uiContent, $match);
            // try RDF/XML link using the UI link (most reliable)
            if (isset($match[1]) && isset($match[2])) {
                $ontologyFile = $match[1].'?'.$match[2];
                $ontologyFileWithApiKey = $ontologyFile .'&apikey='.$this->apiKey;
                $format = 'rdfxml';
                echo PHP_EOL.' - use ui link';
            } else {
                // determine RDF file location, file handle, format and related Graph instance
                $ontologyFile = $ontology['links']['download'];
                $ontologyFileWithApiKey = $ontologyFile.'?apikey='.$this->apiKey;
                $format = $this->guessFormatOnFile($ontologyFileWithApiKey);

                if (null == $format) {
                    echo PHP_EOL.' - unknown format';
                    continue;
                } else {
                    echo PHP_EOL.' - use download link';
                }
            }

            if (
                str_contains($ontologyFileWithApiKey, 'data.bioontology.org/ontologies/DRON/')
                || str_contains($ontologyFileWithApiKey, 'data.bioontology.org/ontologies/HOOM/')
            ) {
                echo PHP_EOL.'takes too long, will be ignored, but downloaded separately';
                continue;
            }

            // set latest files based on format
            if ('ntriples' == $format) {
                $newEntry->setLatestNtriplesFile($ontologyFile);
            } elseif ('rdfxml' == $format) {
                $newEntry->setLatestRdfXmlFile($ontologyFile);
            } elseif ('turtle' == $format) {
                $newEntry->setLatestTurtleFile($ontologyFile);
            } else {
                echo PHP_EOL.' - IGNORED: No valid RDF notation found ('. $format.') for '.$ontologyFile;
                continue;
            }

            // get file handle
            $fileHandle = $this->cache->getLocalFileResourceForFileUrl($ontologyFileWithApiKey);
            if (false === is_resource($fileHandle)) {
                throw new Exception('Could not open related file for '.$ontologyFileWithApiKey);
            }

            // get EasyRdf Graph instance
            $localFilePath = $this->cache->getCachedFilePathForFileUrl($ontologyFileWithApiKey);
            $graph = $this->loadQuadsIntoGraph($fileHandle, $localFilePath);
            fclose($fileHandle);

            if (
                $this->ontologyFileContainsElementsOfCertainTypes($graph)
                || 0 == count($graph)
            ) {
                $this->addFurtherMetadata($newEntry, $graph);

                if (isEmpty($newEntry->getModified())) {
                    // latest access (== latest_submission.released, but only year + month + day)
                    $released = new DateTime($arr['released']);
                    $newEntry->setModified($released->format('Y-m-d'));
                }

                $this->temporaryIndex->storeEntries([$newEntry]);
            } else {
                echo PHP_EOL.' - Aborting, because file '.$localFilePath.' does not contain any ontology related instances';
                echo PHP_EOL;
                continue;
            }
        }
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('BioPortal', 'https://data.bioontology.org/documentation');
    }

    /**
     * Get complete list of ontologies.
     *
     * @return array<array<mixed>>
     *
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getOntologiesToProcess(): array
    {
        // ontology list
        $url = $this->ontologyListUrl.$this->apiKey;

        $content = $this->cache->sendCachedRequest($url, $this->namespace);
        $ontologies = json_decode($content, true);

        echo PHP_EOL.'loaded '.count($ontologies).' entries'.PHP_EOL;

        return $ontologies;
    }
}
