<?php

declare(strict_types=1);

namespace App;

use Curl\Curl;
use EasyRdf\Format;
use EasyRdf\Graph;
use Exception;
use PDO;
use PDOException;
use quickRdf\DataFactory;
use quickRdfIo\RdfIoException;
use quickRdfIo\Util;
use rdfInterface2easyRdf\AsEasyRdf;
use sweetrdf\InMemoryStoreSqlite\Store\InMemoryStoreSqlite;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

function addFurtherMetadata(IndexEntry $indexEntry, Graph $graph): void
{
    // short description / summary
    $properties = ['skos:definition', 'dc11:description', 'dc:description', 'rdfs:comment'];
    $valuesString = getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri());
    $valuesString = strip_tags($valuesString); // remove HTML tags
    $valuesString = preg_replace('/\n/', ' ', $valuesString); // remove newlines
    $valuesString = str_replace(['"', "'"], '', $valuesString); // remove slashes, because of SQLite
    $indexEntry->setSummary($valuesString);

    // license
    $properties = ['dc:license'];
    $valuesString = getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri());
    $indexEntry->setLicenseInformation($valuesString);

    // authors
    $properties = ['dc:creator', 'schema:author'];
    $valuesString = getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri(), ',');
    $indexEntry->setAuthors($valuesString);

    // contributors
    $properties = ['dc:contributor', 'schema:contributor'];
    $valuesString = getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri(), ',');
    $indexEntry->setContributors($valuesString);

    // project page / homepage
    $properties = ['foaf:homepage', 'schema:WebSite', 'schema:url'];
    $valuesString = getLiteralValuesAsString($graph, $properties, $indexEntry->getOntologyIri());
    $indexEntry->setProjectPage($valuesString);
}

/**
 * Removes certain characters from title string.
 */
function cleanTitle(string $str): string
{
    // remove HTML entities like &nbsp;
    $str = html_entity_decode($str);

    // replace new lines
    $str = (string) preg_replace("/[\n\r]/", ' ', $str);

    // replace " with whitespace (or they will interfere with CSV generation)
    $str = str_replace('"', ' ', $str);

    // replace multiple whitespaces with one
    $str = (string) preg_replace("/\s+/", ' ', $str);

    // remove trailing whitespaces
    $str = trim($str);

    return $str;
}

function getLiteralValuesAsString(
    Graph $graph,
    array $properties,
    string $rootResourceIri,
    string $delimiter = ' '
): string {
    $valuesString = '';

    foreach ($properties as $prop) {
        $values = $graph->resource($rootResourceIri)->allLiterals($prop, 'en');
        if (0 == count($values)) {
            // if not entries for english, try without a language
            $values = $graph->resource($rootResourceIri)->allLiterals($prop);
        }

        $values = array_map(function($literal) { return $literal->getValue(); }, $values);
        $valuesString .= implode($delimiter, $values);
    }

    return $valuesString;
}

/**
 * @return array<string,array<string|null>|string|null>
 */
function getOntologyDataAsArray(InMemoryStoreSqlite $store, string $ontologyIri): array
{
    $result = $store->query('SELECT ?p ?o WHERE {<'.$ontologyIri.'> ?p ?o}');

    /**
     * Looks like:
     *
     * [
     *      "http://www.w3.org/1999/02/22-rdf-syntax-ns#type"] => "http://www.w3.org/2002/07/owl#Ontology",
     *      "http://www.w3.org/2002/07/owl#versionIRI"] => "http://emmo.info/emmo/1.0.0-beta/middle/units-extension"
     *      ...
     * ]
     */
    $arr = [];

    // @phpstan-ignore-next-line
    foreach ($result['result']['rows'] as $row) {
        // entry exists and is array
        if (isset($arr[$row['p']]) && is_array($arr[$row['p']])) {
            $arr[$row['p']][] = $row['o'];
        } elseif (isset($arr[$row['p']])) {
            $arr[$row['p']] = [$arr[$row['p']], $row['o']];
        } else {
            $arr[$row['p']] = $row['o'];
        }
    }

    return $arr;
}

/**
 * It seems that empty() is not enough to check, if something is really empty.
 * This function takes care of the edge cases.
 *
 * @see https://stackoverflow.com/questions/718986/checking-if-the-string-is-empty
 */
function isEmpty(string|null $input): bool
{
    if (null === $input) {
        return true;
    } else { // its a string
        $input = trim($input);
        $input = (string) preg_replace('/\s/', '', $input);

        return 0 == strlen($input);
    }
}

/**
 * Very basic approach to check if a given string is an URL.
 */
function isUrl(string $str): bool
{
    return str_starts_with($str, 'http://')
        || str_starts_with($str, 'https://')
        || str_starts_with($str, 'www.');
}

/**
 * Loads the content of a given RDF file into an EasyRdf Graph instance.
 *
 * Be aware: because some ontologies are over 1 GB+ in size, only first x triples are used,
 *           which may result in incomplete meta data about the ontology.
 */
function loadQuadsIntoEasyRdfGraph(string $rdfFileUrl, string|null $format = null): Graph
{
    $maxAmountOfTriples = 1000;

    $content = sendCachedRequest($rdfFileUrl);
    if (isEmpty($format)) {
        $format = Format::guessFormat($content)->getName();
    }

    try {
        echo PHP_EOL.'Current: '.$rdfFileUrl.PHP_EOL;

        /*
         * use quickRdfIo's Util::parse
         */
        $iterator = Util::parse($content, new DataFactory(), $format);
        $i = 0;
        $list = [];
        foreach ($iterator as $quad) {
            $list[] = $quad;
            if ($i++ > $maxAmountOfTriples) {
                break;
            }
        }

        $graph = AsEasyRdf::asEasyRdf($iterator);
        $iterator = null;
        return $graph;
    } catch (RdfIoException $th) {
        echo PHP_EOL.'WARN: quickRdfIo failed with >> '.$th->getMessage();
        echo PHP_EOL.'- try raptor2-utils'.PHP_EOL;

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
 * @throws \Psr\Cache\InvalidArgumentException
 * @throws \quickRdfIo\RdfIoException
 */
function loadQuadsIntoInMemoryStore(string $rdfFileUrl): InMemoryStoreSqlite|null
{
    $maxQuadAmount = 3000;

    // download file and read content
    $rdfFileContent = sendCachedRequest($rdfFileUrl);

    $relevantQuads = [];
    echo PHP_EOL;
    echo PHP_EOL.'- loaded '.PHP_EOL;
    try {
        // parse a file
        $format = Format::guessFormat($rdfFileContent)?->getName();
        $iterator = Util::parse($rdfFileContent, new DataFactory(), $format);
        $i = 0;
        foreach ($iterator as $item) {
            $relevantQuads[] = $item;
            if ($i++ > $maxQuadAmount) {
                // only take a limit amount to avoid the script run too long
                break;
            }
        }
    } catch (RdfIoException $e) {
        throw $e;
    }

    $store = InMemoryStoreSqlite::createInstance();
    $store->addQuads($relevantQuads);

    return $store;
}

/**
 * Cache responses for a while to reduce server load.
 *
 * @throws \Exception if curl found an error
 * @throws \Psr\Cache\InvalidArgumentException
 */
function sendCachedRequest(string $url): string
{
    $cache = new FilesystemAdapter('cached_request', 0, __DIR__.'/../var');

    $key = (string) preg_replace('/[\W]/', '_', $url);

    // ask cache for entry
    // if there isn't one, run HTTP request and return response content
    return $cache->get($key, function (ItemInterface $item) use ($url): string {
        echo PHP_EOL;

        $curl = new Curl();

        // timeout until conntected
        $curl->setConnectTimeout(5);
        // time of curl to execute
        $curl->setTimeout(30);

        $curl->setMaximumRedirects(10);
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, true); // follow redirects
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);

        $curl->get($url);

        if ($curl->isError()) {
            throw new Exception('CURL error: '.$curl->getErrorMessage());
        }

        // lazy approach: we dont care if link exists or not, just if it has parseable content
        return $curl->rawResponse;
    });
}

/**
 * @param array<string,\App\IndexEntry> $temporaryIndex
 *
 * @throws \PDOException
 */
function storeTemporaryIndexIntoSQLiteFile(array $temporaryIndex): void
{
    // create/open SQLite file (= our database)
    $db = new PDO('sqlite:'.SQLITE_FILE_PATH);

    // TODO move to a better place to avoid unneccessary SQL commands
    $db->exec('CREATE TABLE IF NOT EXISTS entry (
        ontology_uri TEXT PRIMARY KEY,
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


    $q = 'INSERT INTO entry (
        ontology_uri,
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

    foreach ($temporaryIndex as $indexEntry) {
        try {
            // build insert query
            // no prepared statements anymore, because they sometimes lead to:
            //      Uncaught PDOException: SQLSTATE[HY000]: General error: 21 bad parameter or other API misuse
            $insertQ = $q;
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

            $db->prepare($insertQ)->execute();
        } catch (PDOException $e) {
            // if an entry with this URI already exists try to fill up empty fields of the DB entry
            if (str_contains($e->getMessage(), 'UNIQUE constraint failed: entry.ontology_uri')) {
                // ignore this case, because existing entries are not altered
                continue;
            } else {
                throw $e;
            }
        }
    }
}

/**
 * Uncompress a .gz file.
 *
 * @throws \Exception
 */
function uncompressGzArchive(string $sourceFilepath, string $targetFilepath): void
{
    // Raising this value may increase performance
    $buffer_size = 16384; // read 4kb at a time

    // Open our files (in binary mode)
    $file = gzopen($sourceFilepath, 'rb');
    if (is_resource($file)) {
        $out_file = fopen($targetFilepath, 'wb');
        if (is_resource($out_file)) {
            // Keep repeating until the end of the input file
            while (false === gzeof($file)) {
                // Read buffer-size bytes
                // Both fwrite and gzread are binary-safe
                fwrite($out_file, (string) gzread($file, $buffer_size));
            }

            // Files are done, close files
            fclose($out_file);
            gzclose($file);
        } else {
            throw new Exception('Could not open target file for uncompressing: '.$sourceFilepath);
        }
    } else {
        throw new Exception('Uncompressing failed, could not open: '.$sourceFilepath);
    }
}

/**
 * @throws \Exception
 */
function urlIsAccessible(string $url, int $timeout = 5, int $maximumRedirects = 10): bool
{
    $curl = new Curl();
    $curl->setOpt(CURLOPT_CONNECT_ONLY, true);
    $curl->setConnectTimeout($timeout);
    $curl->setMaximumRedirects($maximumRedirects);
    $curl->setOpt(CURLOPT_FOLLOWLOCATION, true); // follow redirects
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
    $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);

    $curl->get($url);
    if ($curl->error) {
        return false;
    } else {
        return true;
    }
}
