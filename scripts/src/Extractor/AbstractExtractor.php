<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Cache;
use App\Graph;
use App\IndexEntry;
use App\TemporaryIndex;
use EasyRdf\Format;
use Exception;
use quickRdfIo\Raptor\Parser;
use quickRdfIo\RdfIoException;
use quickRdfIo\Util;
use rdfInterface\DataFactoryInterface;
use Throwable;

use function App\isEmpty;

abstract class AbstractExtractor
{
    protected Cache $cache;
    protected DataFactoryInterface $dataFactory;
    protected Parser $raptorParser;
    protected TemporaryIndex $temporaryIndex;

    /**
     * Namespace for cache.
     *
     * @var non-empty-string
     */
    protected string $namespace;

    public function __construct(Cache $cache, DataFactoryInterface $dataFactory, TemporaryIndex $temporaryIndex)
    {
        $this->cache = $cache;
        $this->dataFactory = $dataFactory;
        $this->temporaryIndex = $temporaryIndex;

        // setup Raptor Utils parser
        $this->raptorParser = new Parser($this->dataFactory);
        $this->raptorParser->setDirPathForTemporaryFiles(ROOT_DIR_PATH.'scripts/var/raptor_temp_files');
    }

    abstract public function getPreparedIndexEntry(): IndexEntry;
    abstract public function run(): void;

    /**
     * Sets/updates meta data in a given IndexEntry instance using a given Graph instance.
     *
     * @throws \InvalidArgumentException
     */
    public function addFurtherMetadata(IndexEntry $indexEntry, Graph $graph): void
    {
        // short description / summary
        $properties = [
            'skos:definition',
            'dc11:description',
            'dc:description',
            'rdfs:comment',
            'schema:description',
        ];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri(), ' ', true);
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setSummary($valuesString);
        }

        // license
        $valuesString = null;
        foreach (['dc:license', 'dc11:license', 'dc:rights', 'dc11:rights', 'schema:license'] as $prop) {
            $valuesString = $this->getLiteralValuesAsString($graph, [$prop], (string) $indexEntry->getOntologyIri(), ' ', true);
            $valuesString = $this->getAlignedLicenseInformation($valuesString);

            if (isEmpty($valuesString)) {
                continue;
            }
        }
        if (false === isEmpty($valuesString)) {
            $indexEntry->setLicenseInformation($valuesString);
        }

        // authors
        $properties = ['dc:creator', 'dc11:creator', 'schema:author'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri(), ',');
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setAuthors($valuesString);
        }

        // contributors
        $properties = ['dc:contributor', 'dc11:', 'schema:contributor'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri(), ',');
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setContributors($valuesString);
        }

        // project page / homepage
        $properties = ['foaf:homepage', 'schema:WebSite', 'schema:url', 'rdfs:seeAlso'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri(), ',');
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setProjectPage($valuesString);
        }

        // version
        $properties = ['owl:versionInfo', 'schema:schemaVersion', 'schema:version'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri(), '', true);
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setVersion($valuesString);
        }

        /*
         * modified (latest file)
         */
        $properties = ['dc:modified', 'dc11:modified', 'schema:dateModified'];
        foreach ($properties as $prop) {
            $values = $graph->getPropertyValues((string) $indexEntry->getOntologyIri(), $prop);

            // create a list of date strings
            $values = array_map(function ($value) {
                if(1 === preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{4}/', $value)) {
                    return $value;
                }
            }, $values);

            // sort entries, take the latest one
            usort($values, function ($a, $b) {
                return $a < $b ? -1 : 1;
            });

            if (0 < count($values)) {
                $indexEntry->setModified($values[0]);
                break;
            }
        }

        // if modified ist still empty but dcterms:created is available, used it
        if (isEmpty($indexEntry->getModified())) {
            $properties = ['dc:created', 'dc11:created', 'schema:dateCreated'];
            foreach ($properties as $prop) {
                $values = $graph->getPropertyValues((string) $indexEntry->getOntologyIri(), $prop);
                // create a list of date strings
                $values = array_map(function ($value) {
                    if(1 === preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{4}/', $value)) {
                        return $value;
                    }
                }, $values);
                // sort entries, take the latest one
                usort($values, function ($a, $b) {
                    return $a < $b ? -1 : 1;
                });

                if (0 < count($values)) {
                    $indexEntry->setModified($values[0]);
                    break;
                }
            }
        }
    }

    /**
     * Removes certain characters from a string.
     */
    public function cleanString(string $str, bool $removeSlashes = true): string
    {
        // remove HTML entities like &nbsp;
        $str = html_entity_decode($str);

        // remove tags
        $str = strip_tags($str);

        // replace new lines
        $str = (string) preg_replace('~[\r\n]+~', '', $str);

        // replace " with whitespace (or they will interfere with CSV generation)
        if ($removeSlashes) {
            $str = str_replace(['"', "'"], ' ', $str);
        }

        // replace multiple whitespaces with one
        $str = (string) preg_replace("/\s+/", ' ', $str);

        // remove trailing whitespaces
        $str = trim($str);

        return $str;
    }

    /**
     * @param list<non-empty-string> $properties
     *
     * @throws \InvalidArgumentException
     */
    public function getLiteralValuesAsString(
        Graph $graph,
        array $properties,
        string $rootResourceIri,
        string $delimiter = ' ',
        bool $onlyUseFirstEntry = false
    ): string {
        $valuesString = '';

        foreach ($properties as $prop) {
            $values = $graph->getPropertyValues($rootResourceIri, $prop, 'en');
            if (0 == count($values)) {
                // if no entries for english, try without a language
                $values = $graph->getPropertyValues($rootResourceIri, $prop);
            }

            // remove blank nodes
            $list = [];
            foreach ($values as $val) {
                if (false === str_contains($val, '_:')) {
                    $list[] = $val;
                }
            }

            if ($onlyUseFirstEntry && 1 < count($list)) {
                $list = [array_values($list)[0]];
            }

            $valuesString .= implode($delimiter, $list);
        }

        return $valuesString;
    }

    protected function getAlignedLicenseInformation(string $value): string
    {
        $list = [
            'https://www.apache.org/licenses/LICENSE-2.0' => 'Apache License 2.0',
            'http://purl.org/NET/rdflicense/cc-by3.0' => 'CC-BY 3.0',
            'http://purl.org/NET/rdflicense/cc-by4.0' => 'CC-BY 4.0',
            'http://creativecommons.org/publicdomain/zero/1.0/' => 'CC0 1.0 DEED',
            'https://creativecommons.org/publicdomain/zero/1.0/' => 'CC0 1.0 DEED',
            'https://creativecommons.org/licenses/by/1.0' => 'CC-BY 1.0',
            'https://creativecommons.org/licenses/by/1.0/' => 'CC-BY 1.0',
            'http://creativecommons.org/licenses/by/2.0' => 'CC-BY 2.0',
            'http://creativecommons.org/licenses/by/2.0/' => 'CC-BY 2.0',
            'http://creativecommons.org/licenses/by/3.0' => 'CC-BY 3.0',
            'http://creativecommons.org/licenses/by/3.0/' => 'CC-BY 3.0',
            'https://creativecommons.org/licenses/by/3.0/' => 'CC-BY 3.0',
            'https://creativecommons.org/licenses/by/4.0' => 'CC-BY 4.0',
            'https://creativecommons.org/licenses/by/4.0/' => 'CC-BY 4.0',
            'http://creativecommons.org/licenses/by/4.0/' => 'CC-BY 4.0',
            'http://creativecommons.org/licenses/by/4.0' => 'CC-BY 4.0',
            'https://creativecommons.org/licenses/by/4.0/legalcode' => 'CC-BY 4.0',
            'Creative Commons Attribution 4.0 International' => 'CC-BY 4.0',
            'Creative Commons Attribution 4.0 International (CC BY 4.0)' => 'CC-BY 4.0',
            'https://creativecommons.org/licenses/by-nc/3.0/legalcode' => 'CC-BY-NC 3.0',
            'https://creativecommons.org/licenses/by-nc/4.0/' => 'CC-BY-NC 4.0',
            'http://creativecommons.org/licenses/by-nc-sa/2.0/' => 'CC-BY-NC-SA 2.0',
            'http://creativecommons.org/licenses/by-nc-sa/3.0/' => 'CC-BY-NC-SA 3.0',
            'https://creativecommons.org/licenses/by-nd/4.0/' => 'CC-BY-ND 4.0',
            'https://creativecommons.org/licenses/by-sa/4.0/' => 'CC-BY-SA 4.0',
            'GNU General Public License' => 'GPL-1.0',
            'http://opensource.org/licenses/MIT' => 'MIT',
            'https://opensource.org/licenses/MIT' => 'MIT',
            'http://www.opendatacommons.org/licenses/pddl/1.0/' => 'PDDL 1.0',
        ];

        // licenses with no related URL
        $list[] = 'BSD-2-Clause';
        $list[] = 'BSD-3-Clause';
        $list[] = 'CC0 1.0 Universal';
        $list[] = 'CC-BY-SA 3.0';
        $list[] = 'GPL-3.0';
        $list[] = 'Information not available';
        $list[] = 'OGC Document License Agreement';
        $list[] = 'W3C Document License (2023)';

        // URL found, get title
        if (isset($list[$value])) {
            return $list[$value];
        } elseif (in_array($value, $list, true)) {
            // known title found, just use it
            return $value;
        } else {
            // no match, so clean string and return it
            return $this->cleanString($value);
        }
    }

    /**
     * @param non-empty-string $fileUrl
     *
     * @throws \Exception
     */
    public function guessFormatOnFile(string $fileUrl): string|null
    {
        try {
            $fileHandle = $this->cache->getLocalFileResourceForFileUrl($fileUrl);
        } catch (Throwable $th) {
            if (
                str_contains($th->getMessage(), 'HTTP/1.1 403 Forbidden')
                || str_contains($th->getMessage(), 'HTTP/1.1 504 Gateway Time-out')
            ) {
                echo PHP_EOL.$th->getMessage();
                return null;
            } else {
                throw $th;
            }
        }

        if (false === is_resource($fileHandle)) {
            throw new Exception('Could not open related file for '.$fileUrl);
        }

        $lengthInMb = 1024 * 100;
        $str = (string) fread($fileHandle, $lengthInMb);

        fclose($fileHandle);

        $format = Format::guessFormat($str)?->getName() ?? null;
        if (null == $format) {
            // it only uses the first 1024 bytes, ... try with more bytes
            if (str_contains($str, '<rdf:')) {
                $format = 'rdfxml';
            }
        }

        return $format;
    }

    /**
     * @param resource|\rdfInterface\QuadIteratorInterface $input
     *
     * @return array<\rdfInterface\QuadInterface>
     *
     * @throws \Throwable
     */
    protected function readQuadsToList($input, string|null $format = null): array
    {
        $maxAmountOfTriples = 40000;

        if (is_resource($input)) {
            $input = Util::parse($input, $this->dataFactory, $format);
        }

        /*
         * use quickRdfIo's Util::parse
         */
        $i = 0;
        $list = [];
        foreach ($input as $quad) {
            $list[] = $quad;
            if ($i++ > $maxAmountOfTriples) {
                break;
            }
        }

        return $list;
    }

    /**
     * Loads the content of a given RDF file into a Graph instance.
     *
     * Be aware: because some ontologies are over 1 GB+ in size, only first x triples are used,
     *           which may result in incomplete meta data about the ontology.
     *
     * @param resource $fileHandle
     *
     * @throws \Throwable
     */
    protected function loadQuadsIntoGraph($fileHandle, string $localFilePath, string|null $format = null): Graph
    {
        try {
            // TODO rethink that
            if (
                str_contains($localFilePath, 'data_bioontology_org_ontologies_TXPO')
            ) {
                throw new RdfIoException('quickRdfIo is known to fail to parse it, therefore jump to rapper');
            }

            return new Graph($this->readQuadsToList($fileHandle, $format));
        } catch (Throwable $th) {
            if (
                $th instanceof RdfIoException
                || str_contains($th->getMessage(), 'on line')
            ) {
                if (isEmpty($format)) {
                    // leave it to the parser
                    $this->raptorParser->setFormat(null);
                } else {
                    $this->raptorParser->setFormat($format);
                }

                /*
                 * the following part takes the downloaded RDF file, parses it with rapper, transforms the content
                 * to ntriples, stores it in a temp. file and reads it back in later on.
                 *
                 * this way we can compute big files without running out of memory, because only a chunk of the file
                 * is taken to build the Graph instance.
                 */
                echo PHP_EOL.'quickRdfIo failed with ERROR: '.$th->getMessage();
                echo PHP_EOL.'- trying rapper (use format = '.$format.')'.PHP_EOL;

                $fileHandle = fopen($localFilePath, 'r');
                if (false == $fileHandle) {
                    throw new Exception('Could not open file named: '.$localFilePath);
                }

                try {
                    $iterator = $this->raptorParser->parseStream($fileHandle);
                    $list = $this->readQuadsToList($iterator, 'ntriples');
                    fclose($fileHandle);
                    return new Graph($list);
                } catch(Throwable $th) {
                    echo PHP_EOL;
                    echo PHP_EOL.'- ERR: '.$th->getMessage();
                    echo PHP_EOL;
                    return new Graph([]);
                }

            } else {
                throw $th;
            }
        }
    }

    /**
     * Checks if ontology file contains elements of a certain type.
     *
     * @throws \InvalidArgumentException
     */
    protected function ontologyFileContainsElementsOfCertainTypes(Graph $graph): bool
    {
        return
            $graph->hasInstancesOfType('owl:Ontology')
            || $graph->hasInstancesOfType('owl:Class')
            || $graph->hasInstancesOfType('rdf:Property')
            || $graph->hasInstancesOfType('rdfs:Class')
            || $graph->hasInstancesOfType('skos:Concept')
        ;
    }
}
