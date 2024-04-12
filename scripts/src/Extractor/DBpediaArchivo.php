<?php

declare(strict_types=1);

namespace App\Extractor;

use App\IndexEntry;
use Exception;

use function App\isEmpty;

class DBpediaArchivo extends AbstractExtractor
{
    protected string $namespace = 'extractor_dbpedia_archivo';
    private string $ontologyListUrl = 'https://archivo.dbpedia.org/list';

    public function run(): void
    {
        echo PHP_EOL;
        echo 'DBpediaArchivo::run:';
        echo PHP_EOL;

        foreach ($this->getOntologiesToProcess() as $indexEntry) {
            echo PHP_EOL;
            $mem = number_format((memory_get_peak_usage(false) / 1024 / 1024), 2, ',', '.').' MB';
            echo '------------------------------- '.$mem.' ----------------------------------';
            echo PHP_EOL;
            echo 'Next: '.$indexEntry->getOntologyTitle();
            echo ' >> '.$indexEntry->getLatestNtFile();

            $toBeIgnoredForNow = [
                'PRotein Ontology',
            ];
            if (in_array($indexEntry->getOntologyTitle(), $toBeIgnoredForNow)) {
                echo PHP_EOL.' - manual IGNORE, because they will run into timeout';
                continue;
            }

            // fill remaining metadata by downloading RDF file to extract further meta data
            try {
                $fileHandle = $this->cache->getLocalFileResourceForFileUrl($indexEntry->getLatestNtFile());
                $graph = $this->loadQuadsIntoEasyRdfGraph($fileHandle, $indexEntry->getLatestNtFile(), 'ntriples');
                echo PHP_EOL.' - graph created';
            } catch (Exception $e) {
                if (str_contains($e->getMessage(), 'CURLE_OPERATION_TIMEOUTED')) {
                    echo PHP_EOL.' - TIMEOUT, ignored';
                    continue;
                } elseif (str_contains($e->getMessage(), 'INTERNAL SERVER ERROR')) {
                    echo PHP_EOL.' - IGNORED, because CURL error: '.$e->getMessage();
                    continue;
                } else {
                    throw $e;
                }
            }

            $this->addFurtherMetadata($indexEntry, $graph);
            echo PHP_EOL.' - metadata added';
            $graph = null;

            $this->storeTemporaryIndexIntoSQLiteFile([$indexEntry]);
        }
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('DBpedia Archivo', $this->ontologyListUrl);
    }

    /**
     * Ask DBpedia Archivo for the complete list of ontologies.
     *
     * @return list<\App\IndexEntry>
     */
    public function getOntologiesToProcess(): array
    {
        $result = [];
        $html = file_get_contents($this->ontologyListUrl);

        $numberOfOntologyEntries = preg_match_all('/<tr>(.*?)<\/tr>/sim', $html, $ontologyEntries);

        if (0 < $numberOfOntologyEntries) {
            // go through received ontologies
            foreach ($ontologyEntries[1] as $ontologyEntryHtml) {
                if (str_contains($ontologyEntryHtml, '<th ')) {
                    continue;
                }

                // new entry in temporary index
                $newEntry = $this->getPreparedIndexEntry();

                // info page + title/name of ontology
                preg_match('/<td>\s*\n*<a href="(\/info\?o=.*?)">(.*?)</sim', $ontologyEntryHtml, $data);
                if (isset($data[1]) && false === isEmpty($data[1])) {
                    $newEntry->setSourcePageUrl('https://archivo.dbpedia.org/'.$data[1]);
                }
                if (isset($data[1]) && false === isEmpty($data[2])) {
                    $newEntry->setOntologyTitle($this->cleanString($data[2]));
                } else {
                    echo PHP_EOL.'no ontology title, so ignore entry with HTML: '.$ontologyEntryHtml;

                    // no title means, no valid meta data, therefore stop
                    continue;
                }

                // URI of ontology
                preg_match('/<td>\s*<a href="\/info\?o=(.*?)"/sim', $ontologyEntryHtml, $uri);
                if (isset($uri[1]) && false === isEmpty($uri[1])) {
                    $newEntry->setontologyIri($uri[1]);

                    // ignore entire entry if ontology is already known
                    if ($this->hasOntology($newEntry->getOntologyIri())) {
                        echo PHP_EOL.'IGNORE: already in temporary index > '.$newEntry->getOntologyTitle();
                        echo ' >> '. $newEntry->getOntologyIri();
                        continue;
                    }
                } else {
                    echo PHP_EOL.'no ontology URI, so ignore entry with HTML: '.$ontologyEntryHtml;

                    // no URI means, no valid meta data, therefore stop
                    continue;
                }

                // latest update date
                preg_match('/nt<\/a>.*?>([0-9]{4})\.([0-9]{2})\.([0-9]{2})/sim', $ontologyEntryHtml, $latest);
                if (isset($latest[1]) && false === isEmpty($latest[1])) {
                    $latestAccess = $latest[1].'-'.$latest[2].'-'.$latest[3];
                    $newEntry->setLatestAccess($latestAccess);
                } else {
                    $message = 'Can not read latest timestamp field for '.$newEntry->getOntologyIri();
                    $message .= ' // RAW HTML : '.$ontologyEntryHtml;
                    throw new Exception($message);
                }

                /*
                 * latest OWL,TTL,... file
                 */
                $iriUrlEncoded = urlencode($newEntry->getOntologyIri());
                $newEntry->setLatestNtFile('http://archivo.dbpedia.org/download?o='.$iriUrlEncoded.'&f=nt');
                $newEntry->setLatestRdfXmlFile('http://archivo.dbpedia.org/download?o='.$iriUrlEncoded.'&f=owl');
                $newEntry->setLatestTtlFile('http://archivo.dbpedia.org/download?o='.$iriUrlEncoded.'&f=ttl');

                $result[] = $newEntry;
            }
        } else {
            // nothing found or error
            throw new Exception('No ontology entries found at '.$this->ontologyListUrl);
        }

        return $result;
    }
}
