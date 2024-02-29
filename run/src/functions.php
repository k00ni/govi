<?php

declare(strict_types=1);

namespace App;

use Exception;
use PDO;
use PDOException;

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
 * @param array<string,\App\IndexEntry> $temporaryIndex
 *
 * @throws \PDOException
 */
function storeTemporaryIndexIntoSQLiteFile(array $temporaryIndex): void
{
    // create/open SQLite file (= our database)
    $db = new PDO('sqlite:'.SQLITE_FILE_PATH);

    $db->exec('CREATE TABLE IF NOT EXISTS entry (
        ontology_uri TEXT PRIMARY KEY,
        ontology_title TEXT,
        latest_ntriples_file TEXT,
        latest_rdfxml_file TEXT,
        latest_turtle_file TEXT,
        latest_access TEXT,
        source_title TEXT,
        source_url TEXT
    )');

    /*
     * create prepared statement: faster than standard SQL and more resilient
     *
     * FYI: https://www.php.net/manual/en/pdo.prepared-statements.php
     */
    $stmt = $db->prepare('INSERT INTO entry (
            ontology_uri,
            ontology_title,
            latest_ntriples_file,
            latest_rdfxml_file,
            latest_turtle_file,
            latest_access,
            source_title,
            source_url
        ) VALUES (?,?,?,?,?,?,?,?);');

    foreach ($temporaryIndex as $indexEntry) {
        try {
            $stmt->execute([
                $indexEntry->getOntologyUri(),
                addslashes((string) $indexEntry->getOntologyTitle()),
                $indexEntry->getLatestNtFile(),
                $indexEntry->getLatestRdfXmlFile(),
                $indexEntry->getLatestTtlFile(),
                $indexEntry->getLatestAccess(),
                $indexEntry->getSourceTitle(),
                $indexEntry->getSourceUrl(),
            ]);
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
