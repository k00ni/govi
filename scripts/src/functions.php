<?php

declare(strict_types=1);

namespace App;

use Curl\Curl;
use Exception;
use sweetrdf\InMemoryStoreSqlite\Store\InMemoryStoreSqlite;

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
 * Uncompress a .gz file.
 *
 * @throws \Exception
 */
function uncompressGzArchive(string $sourceFilepath, string $targetFilepath): void
{
    // Raising this value may increase performance
    $buffer_size = 16384; // read x kb at a time

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
