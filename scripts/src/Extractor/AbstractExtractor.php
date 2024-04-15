<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Cache;
use App\IndexEntry;
use App\TemporaryIndex;
use DateMalformedStringException;
use DateTime;
use EasyRdf\Format;
use EasyRdf\Graph;
use EasyRdf\Literal;
use EasyRdf\Literal\Date;
use Exception;
use quickRdfIo\RdfIoException;
use quickRdfIo\Util;
use rdfInterface\BlankNodeInterface;
use rdfInterface\DataFactoryInterface;
use rdfInterface\LiteralInterface;
use rdfInterface\NamedNodeInterface;
use Throwable;

use function App\isEmpty;

abstract class AbstractExtractor
{
    protected Cache $cache;
    protected DataFactoryInterface $dataFactory;
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
        $properties = ['skos:definition', 'dc11:description', 'dc:description', 'rdfs:comment'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri());
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setSummary($valuesString);
        }

        // license
        $valuesString = null;
        foreach (['dc:license', 'dc11:rights'] as $prop) {
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
        $properties = ['dc:creator', 'dc11:creatror', 'schema:author'];
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
        $properties = ['foaf:homepage', 'schema:WebSite', 'schema:url'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, (string) $indexEntry->getOntologyIri());
        $valuesString = $this->cleanString($valuesString);
        if (false === isEmpty($valuesString)) {
            $indexEntry->setProjectPage($valuesString);
        }

        /*
         * latest access (latest file)
         */
        $properties = ['dc:modified', 'dc11:modified'];
        foreach ($properties as $prop) {
            $values = $graph->resource($indexEntry->getOntologyIri())->allLiterals($prop);

            // create a list of datetime strings
            $values = array_map(function ($value) {
                if ($value instanceof DateTime || $value instanceof Date) {
                    return $value->format('Y-m-d');
                } else {
                    return $value->getValue();
                }
            }, $values);

            // sort entries, take the latest one
            usort($values, function ($a, $b) {
                return $a < $b ? -1 : 1;
            });

            if (0 < count($values)) {
                $indexEntry->setLatestAccess($values[0]);
                break;
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
    protected function getLiteralValuesAsString(
        Graph $graph,
        array $properties,
        string $rootResourceIri,
        string $delimiter = ' ',
        bool $onlyUseFirstEntry = false
    ): string {
        $valuesString = '';

        foreach ($properties as $prop) {
            $values = $graph->resource($rootResourceIri)->allLiterals($prop, 'en');
            if (0 == count($values)) {
                // if no entries for english, try without a language
                $values = $graph->resource($rootResourceIri)->allLiterals($prop);
            }

            if ($onlyUseFirstEntry && 1 < count($values)) {
                $values = [array_values($values)[0]];
            }

            $values = array_map(function ($literal) {
                if ($literal->getValue() instanceof DateTime) {
                    return $literal->getValue()->format('Y-m-d');
                } else {
                    return $literal->getValue();
                }
            }, $values);
            $valuesString .= implode($delimiter, $values);
        }

        return $valuesString;
    }

    /**
     * @param non-empty-string $fileUrl
     *
     * @throws \Exception
     */
    public function guessFormatOnFile(string $fileUrl): string|null
    {
        $fileHandle = $this->cache->getLocalFileResourceForFileUrl($fileUrl);
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
     * This part could be done more easily with AsEasyRdf::AsEasyRdf, but some ontologies
     * contain triples, which have object values that don't correspond with their data types.
     * EasyRdf trusts data types, which might lead to an exception if the transformation
     * from the string value into an actual class fails (e.g. xsd:dateTime).
     * e.g. DateMalformedStringException if value is not a valid datetime string.
     *
     * FYI: https://github.com/sweetrdf/quickRdfIo/issues/8
     *
     * @param list<\rdfInterface\QuadInterface> $list
     *
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    protected function generateEasyRdfGraphForQuadList(iterable $list): Graph
    {
        $graph = new Graph();

        foreach ($list as $quad) {
            $res = $graph->resource($quad->getSubject()->getValue());
            $o = $quad->getObject();

            if ($o instanceof BlankNodeInterface || $o instanceof NamedNodeInterface) {
                $object = $graph->resource($o->getValue());
            } elseif ($o instanceof LiteralInterface) {
                // check data type ...
                $dataType = isEmpty($o->getLang()) ? $o->getDatatype() : null;
                if ('http://www.w3.org/2001/XMLSchema#dateTime' == $dataType) {
                    // if data type is xsd:dateTime, try to convert the value before using it to
                    // avoid EasyRdf to fail when filling the Graph instance
                    try {
                        new DateTime($o->getValue());
                    } catch (DateMalformedStringException) {
                        // value is not a valid date time, therefore ignoring date type
                        $dataType = null;
                    }
                }

                $object = new Literal($o->getValue(), $o->getLang(), $dataType);
            } else {
                throw new Exception('This should never be reached...');
            }

            $res->add($quad->getPredicate()->getValue(), $object);
        }


        return $graph;
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
     * Loads the content of a given RDF file into an EasyRdf Graph instance.
     *
     * Be aware: because some ontologies are over 1 GB+ in size, only first x triples are used,
     *           which may result in incomplete meta data about the ontology.
     *
     * @param resource $fileHandle
     *
     * @throws \Throwable
     */
    protected function loadQuadsIntoEasyRdfGraph(
        $fileHandle,
        string $rdfFileUrl,
        string|null $format = null
    ): Graph {
        $maxAmountOfTriples = 10000;

        try {
            /*
             * use quickRdfIo's Util::parse
             */
            $i = 0;
            $list = [];
            foreach (Util::parse($fileHandle, $this->dataFactory, $format) as $quad) {
                $list[] = $quad;
                if ($i++ > $maxAmountOfTriples) {
                    break;
                }
            }
            return $this->generateEasyRdfGraphForQuadList($list);
        } catch (Throwable $th) {
            if (
                $th instanceof RdfIoException
                || str_contains($th->getMessage(), 'on line')
            ) {
                echo PHP_EOL.' - quickRdfIo failed, trying rapper'.PHP_EOL;
                /*
                 * use rapper command to read the RDF file and return nquads
                 */
                if (isEmpty($format)) {
                    // FYI: https://librdf.org/raptor/rapper.html
                    $format = '--guess';
                } else {
                    $format = '-i '.substr((string) $format, 0, 20);
                }

                $command = 'rapper '.$format.' -o ntriples '.$rdfFileUrl;
                $nquads = (string) shell_exec($command);
                // limit amount of entries
                $triples = explode(PHP_EOL, $nquads);
                $triples = array_slice($triples, 0, $maxAmountOfTriples);

                return new Graph(null, implode(PHP_EOL, $triples), 'ntriples');
            } else {
                throw $th;
            }
        }
    }
}
