<?php

declare(strict_types=1);

namespace App\Extractor;

use App\Cache;
use App\IndexEntry;
use DateMalformedStringException;
use DateTime;
use EasyRdf\Graph;
use EasyRdf\Literal;
use Iterator;
use PDO;
use PDOException;
use quickRdfIo\RdfIoException;
use quickRdfIo\Util;
use rdfInterface2easyRdf\AsEasyRdf;
use rdfInterface\BlankNodeInterface;
use rdfInterface\DataFactoryInterface;
use rdfInterface\NamedNodeInterface;

use function App\isEmpty;

abstract class AbstractExtractor
{
    protected Cache $cache;
    protected DataFactoryInterface $dataFactory;

    protected string $insertIntoQueryHead = 'INSERT INTO entry (
        ontology_iri,
        ontology_title,
        summary,
        license_information,
        authors,
        contributors,
        project_page,
        source_page_url,
        latest_json_ld_file,
        latest_n3_file,
        latest_ntriples_file,
        latest_rdfxml_file,
        latest_turtle_file,
        latest_access,
        source_title,
        source_url
    ) VALUES (';

    /**
     * Namespace for cache.
     *
     * @var non-empty-string
     */
    protected string $namespace;
    protected PDO $temporaryIndexDb;

    public function __construct(Cache $cache, DataFactoryInterface $dataFactory)
    {
        $this->cache = $cache;
        $this->dataFactory = $dataFactory;

        // create/open SQLite file with the temporary index
        $this->temporaryIndexDb = new PDO('sqlite:'.SQLITE_FILE_PATH);
        $this->temporaryIndexDb->exec('CREATE TABLE IF NOT EXISTS entry (
            ontology_iri TEXT PRIMARY KEY,
            ontology_title TEXT,
            summary TEXT,
            license_information TEXT,
            authors TEXT,
            contributors TEXT,
            project_page TEXT,
            source_page_url TEXT,
            latest_json_ld_file TEXT,
            latest_n3_file TEXT,
            latest_ntriples_file TEXT,
            latest_rdfxml_file TEXT,
            latest_turtle_file TEXT,
            latest_access TEXT,
            source_title TEXT,
            source_url TEXT
        )');
    }

    abstract public function getPreparedIndexEntry(): IndexEntry;
    abstract public function run(): void;

    public function addFurtherMetadata(IndexEntry $indexEntry, Graph $graph): void
    {
        // short description / summary
        $properties = ['skos:definition', 'dc11:description', 'dc:description', 'rdfs:comment'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri());
        $valuesString = $this->cleanString($valuesString);
        $indexEntry->setSummary($valuesString);

        // license
        $valuesString = null;
        foreach (['dc:license', 'dc11:rights'] as $prop) {
            $valuesString = $this->getLiteralValuesAsString($graph, [$prop], $indexEntry->getOntologyIri(), ' ', true);
            $valuesString = $this->getAlignedLicenseInformation($valuesString);

            if (isEmpty($valuesString)) {
                continue;
            }
        }
        $indexEntry->setLicenseInformation($valuesString);

        // authors
        $properties = ['dc:creator', 'schema:author'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri(), ',');
        $valuesString = $this->cleanString($valuesString);
        $indexEntry->setAuthors($valuesString);

        // contributors
        $properties = ['dc:contributor', 'schema:contributor'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri(), ',');
        $valuesString = $this->cleanString($valuesString);
        $indexEntry->setContributors($valuesString);

        // project page / homepage
        $properties = ['foaf:homepage', 'schema:WebSite', 'schema:url'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri());
        $valuesString = $this->cleanString($valuesString);
        $indexEntry->setProjectPage($valuesString);

        // latest access
        $properties = ['dc:modified', 'dc11:modified'];
        $valuesString = $this->getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri());
        $valuesString = $this->cleanString($valuesString);
        $value = new DateTime($valuesString);
        $indexEntry->setLatestAccess($value->format('Y-m-d'));
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
            $str = (string) str_replace(['"', "'"], ' ', $str);
        }

        // replace multiple whitespaces with one
        $str = (string) preg_replace("/\s+/", ' ', $str);

        // remove trailing whitespaces
        $str = trim($str);

        return $str;
    }

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
     * @param non-empty-string $iri
     */
    protected function hasOntology(string $iri): bool
    {
        $stmt = $this->temporaryIndexDb->prepare('SELECT ontology_iri FROM entry WHERE ontology_iri = ?');
        $stmt->execute([$iri]);
        foreach ($stmt->getIterator() as $entry) {
            return true;
        }

        return false;
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
            'https://creativecommons.org/licenses/by/4.0/' => 'CC-BY 4.0',
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
        } elseif (in_array($value, $list)) {
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
     */
    protected function loadQuadsIntoEasyRdfGraph($fileHandle, string $rdfFileUrl, string $format): Graph
    {
        try {
            /*
             * use quickRdfIo's Util::parse
             */
            $iterator = Util::parse($fileHandle, $this->dataFactory, $format);

            $i = 0;
            $list = [];
            $maxAmountOfTriples = 10000;
            foreach ($iterator as $quad) {
                echo $quad;
                $list[] = $quad;
                if ($i++ > $maxAmountOfTriples) {
                    break;
                }
            }

            echo PHP_EOL.'used '.count($list).' triples to build Graph instance';

            return $this->generateEasyRdfGraphForQuadList($list);
        } catch (RdfIoException) {
            echo PHP_EOL.' - quickRdfIo failed, trying rapper'.PHP_EOL;
            /*
             * use rapper command to read the RDF file and return nquads
             */
            $command = 'rapper -i '.$format .' -o ntriples '.$rdfFileUrl;
            $nquads = (string) shell_exec($command);
            // limit amount of entries
            $triples = explode(PHP_EOL, $nquads);
            $triples = array_slice($triples, 0, $maxAmountOfTriples);

            return new Graph(null, implode(PHP_EOL, $triples), 'ntriples');
        }
    }

    /**
     * This part could be done more easily with AsEasyRdf::AsEasyRdf, but some ontologies
     * contain triples, which have object values that don't correspond with their data types.
     * EasyRdf trusts data types, which might lead to an exception if the transformation
     * from the string value into an actual class fails (e.g. xsd:dateTime).
     * e.g. DateMalformedStringException if value is not a valid datetime string.
     *
     * FYI: https://github.com/sweetrdf/quickRdfIo/issues/8
     */
    protected function generateEasyRdfGraphForQuadList(iterable $list): Graph
    {
        $graph = new Graph();

        echo PHP_EOL;

        foreach ($list as $quad) {
            echo '+';

            $res = $graph->resource($quad->getSubject()->getValue());
            $o = $quad->getObject();

            if ($o instanceof BlankNodeInterface || $o instanceof NamedNodeInterface) {
                $object = $graph->resource($o->getValue());
            } else {
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
            }

            $res->add($quad->getPredicate()->getValue(), $object);
        }


        return $graph;
    }

    /**
     * @param array<string,\App\IndexEntry> $temporaryIndex
     *
     * @throws \PDOException
     */
    protected function storeTemporaryIndexIntoSQLiteFile(array $temporaryIndex): void
    {
        foreach ($temporaryIndex as $indexEntry) {
            try {
                // build insert query
                // no prepared statements anymore, because they sometimes lead to:
                //      Uncaught PDOException: SQLSTATE[HY000]: General error: 21 bad parameter or other API misuse
                $insertQ = $this->insertIntoQueryHead;
                $insertQ .= '"'.implode('","', [
                    $indexEntry->getOntologyIri(),
                    addslashes((string) $indexEntry->getOntologyTitle()),
                    addslashes((string) $indexEntry->getSummary()),
                    (string) $indexEntry->getLicenseInformation(),
                    addslashes((string) $indexEntry->getAuthors()),
                    addslashes((string) $indexEntry->getContributors()),
                    $indexEntry->getProjectPage(),
                    $indexEntry->getSourcePageUrl(),
                    // files
                    $indexEntry->getLatestJsonLdFile(),
                    $indexEntry->getLatestN3File(),
                    $indexEntry->getLatestNtFile(),
                    $indexEntry->getLatestRdfXmlFile(),
                    $indexEntry->getLatestTtlFile(),
                    $indexEntry->getLatestAccess(),
                    // source
                    $indexEntry->getSourceTitle(),
                    $indexEntry->getSourceUrl(),
                ]).'");';

                $this->temporaryIndexDb->prepare($insertQ)->execute();
            } catch (PDOException $e) {
                // if an entry with this URI already exists try to fill up empty fields of the DB entry
                if (str_contains($e->getMessage(), 'UNIQUE constraint failed: entry.ontology_iri')) {
                    // ignore this case, because existing entries are not altered
                    continue;
                } else {
                    throw $e;
                }
            }
        }
    }
}
