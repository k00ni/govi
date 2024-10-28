<?php

declare(strict_types=1);

namespace App\Extractor;

use App\IndexEntry;
use DateTime;
use DateTimeZone;
use EasyRdf\Format;
use Exception;
use Throwable;

use function App\isEmpty;
use function App\isUrl;

/**
 * Read ontology information from OLS (https://www.ebi.ac.uk/ols4/):
 *
 * REST API: https://www.ebi.ac.uk/ols4/api/ontologies
 *
 * Swagger documentation: https://www.ebi.ac.uk/ols4/swagger-ui/index.html#/
 */
class OntologyLookupService extends AbstractExtractor
{
    protected string $namespace = 'extractor_ontology_lookup_service';
    private string $ontologyListUrl = 'https://www.ebi.ac.uk/ols4/api/ontologies';

    /**
     * @throws \Exception
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function run(): void
    {
        echo PHP_EOL;
        echo '------------------------------------------------';
        echo PHP_EOL;
        echo 'Ontology Lookup Service - Extraction started ...';
        echo PHP_EOL;

        $html = (string) file_get_contents($this->ontologyListUrl);
        /** @var array{page:array{totalPages:string}} */
        $jsonArr = json_decode($html, true);
        $totalNumberOfPages = (int) $jsonArr['page']['totalPages'];
        $urlPart = 'https://www.ebi.ac.uk/ols4/api/ontologies?page=';

        if (0 < $totalNumberOfPages) {
            // go through each page ...
            for ($page = 0; $page <= $totalNumberOfPages; ++$page) {
                echo PHP_EOL;
                echo PHP_EOL;
                echo '---------------------------- Page: '.$page.' ---------------------------------';
                echo PHP_EOL;

                $html = $this->cache->sendCachedRequest($urlPart.$page, $this->namespace);
                /** @var array<mixed> */
                $jsonArr = json_decode($html, true);

                if (false === isset($jsonArr['_embedded'])) {
                    continue;
                }

                // go through ontology list ...
                foreach ($jsonArr['_embedded']['ontologies'] as $ontology) {
                    echo PHP_EOL;
                    echo PHP_EOL;
                    echo '#################################';
                    echo PHP_EOL;
                    echo 'Next: '.$ontology['_links']['terms']['href'];

                    $newEntry = $this->getPreparedIndexEntry();

                    // load to terms data with further information
                    try {
                        $terms = $this->cache->sendCachedRequest($ontology['_links']['terms']['href'], $this->namespace);
                    } catch (Throwable $th) {
                        if (str_contains($th->getMessage(), 'CURL error: HTTP/1.1 500')) {
                            echo PHP_EOL.$th->getMessage();
                            continue;
                        } else {
                            throw $th;
                        }
                    }

                    $termsJsonArr = json_decode($terms, true);

                    if (isset($termsJsonArr['status'])) {
                        echo PHP_EOL;
                        echo PHP_EOL;
                        echo PHP_EOL;
                        echo 'ERR with status: '.$termsJsonArr['status'];
                        echo PHP_EOL;
                        var_dump($termsJsonArr);
                        continue;
                    } elseif (false === isset($termsJsonArr['_embedded'])) {
                        echo PHP_EOL;
                        echo PHP_EOL;
                        echo PHP_EOL;
                        echo 'ERR: key _embedded not set:';
                        echo PHP_EOL;
                        var_dump($termsJsonArr);
                        continue;
                    }

                    // many terms are returned, but ontology IRI is available in each term entry
                    $newEntry->setOntologyIri($termsJsonArr['_embedded']['terms'][0]['ontology_iri']);

                    echo PHP_EOL;
                    echo ' - Ontology IRI: '.$newEntry->getOntologyIri();
                    echo PHP_EOL;

                    if ($this->temporaryIndex->hasEntry((string) $newEntry->getOntologyIri())) {
                        echo ' - already part of index';
                        continue;
                    }

                    $fileLocation = $ontology['config']['fileLocation'] ?? null;
                    if (null === $fileLocation || false === isUrl($fileLocation)) {
                        // TODO find another way to get it included
                        continue;
                    }

                    // determine RDF file location, file handle, format and related Graph instance
                    $ontologyFileLocation = $ontology['config']['fileLocation'];
                    $fileHandle = $this->cache->getLocalFileResourceForFileUrl($ontologyFileLocation);
                    if (false === is_resource($fileHandle)) {
                        throw new Exception('Could not open related file for '.$ontologyFileLocation);
                    }

                    $format = $this->guessFormatOnFile($ontologyFileLocation);
                    $localFilePath = $this->cache->getCachedFilePathForFileUrl($ontologyFileLocation);
                    $graph = $this->loadQuadsIntoGraph($fileHandle, $localFilePath);
                    fclose($fileHandle);

                    // if title is empty, try to load file and get it this way
                    if (isEmpty($ontology['config']['title'])) {
                        $title = (string) $graph->getLabel((string) $newEntry->getOntologyIri());
                        if (false === isEmpty($title)) {
                            $newEntry->setOntologyTitle($title);
                        } else {
                            echo PHP_EOL;
                            echo PHP_EOL;
                            echo 'WARN: '.$newEntry->getOntologyIri().' ignored, because no title found.';
                            echo PHP_EOL;
                            continue;
                        }
                    } else {
                        $newEntry->setOntologyTitle($ontology['config']['title']);
                    }

                    // set latest access
                    $uploaded = new DateTime($ontology['updated'], new DateTimeZone('UTC'));
                    $newEntry->setModified($uploaded->format('Y-m-d'));

                    // set appropriate file
                    if ('json' == $format) {
                        $newEntry->setLatestJsonLdFile($ontologyFileLocation);
                    } elseif ('ntriples' == $format) {
                        $newEntry->setLatestNtriplesFile($ontologyFileLocation);
                    } elseif ('rdfxml' == $format) {
                        $newEntry->setLatestRdfXmlFile($ontologyFileLocation);
                    } elseif ('turtle' == $format) {
                        $newEntry->setLatestTurtleFile($ontologyFileLocation);
                    } else {
                        throw new Exception('Unknown file format ('.$format.') for '.$ontologyFileLocation);
                    }

                    if ($this->ontologyFileContainsElementsOfCertainTypes($graph)) {
                        $this->addFurtherMetadata($newEntry, $graph);
                        $this->temporaryIndex->storeEntries([$newEntry]);
                    } else {
                        echo PHP_EOL.' - Aborting, because file '.$localFilePath.' does not contain any ontology related instances';
                        echo PHP_EOL;
                        continue;
                    }
                }
            }
        } else {
            throw new Exception('Could not determine total number of pages.');
        }
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('Ontology Lookup Service (OLS)', $this->ontologyListUrl);
    }
}
