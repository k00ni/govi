<?php

declare(strict_types=1);

namespace App;

use Exception;
use PDO;
use PDOException;

class TemporaryIndex
{
    /**
     * @var string
     */
    private string $insertIntoQueryHead = '';

    /**
     * @var array<string>
     */
    private array $columnList = [
        'ontology_title',
        'ontology_iri',
        'summary',
        'license_information',
        'authors',
        'contributors',
        'project_page',
        'source_page',
        'latest_json_ld_file',
        'latest_n3_file',
        'latest_ntriples_file',
        'latest_rdfxml_file',
        'latest_turtle_file',
        'modified',
        'version',
        'source_title',
        'source_url'
    ];

    protected PDO $temporaryIndexDb;

    /**
     * @throws \PDOException
     */
    public function __construct(string|null $customPath)
    {
        // create/open SQLite file with the temporary index
        $this->temporaryIndexDb = new PDO('sqlite:'.$customPath);

        $this->temporaryIndexDb->exec('CREATE TABLE IF NOT EXISTS entry (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ontology_title TEXT,
            ontology_iri TEXT UNIQUE,
            summary TEXT,
            license_information TEXT,
            authors TEXT,
            contributors TEXT,
            project_page TEXT,
            source_page TEXT,
            latest_json_ld_file TEXT,
            latest_n3_file TEXT,
            latest_ntriples_file TEXT,
            latest_rdfxml_file TEXT,
            latest_turtle_file TEXT,
            modified TEXT,
            version TEXT,
            source_title TEXT,
            source_url TEXT
        )');

        $this->insertIntoQueryHead = 'INSERT INTO entry ('.implode(', ', $this->columnList).') VALUES (';
    }

    /**
     * @return array<int|string,mixed>
     *
     * @throws \PDOException
     */
    public function getEntryDataAsArray(string $iri): array|null
    {
        $stmt = $this->temporaryIndexDb->prepare('SELECT * FROM entry WHERE ontology_iri = ?');
        $stmt->execute([$iri]);
        foreach ($stmt->getIterator() as $entry) {
            return $entry;
        }

        return null;
    }

    /**
     * @throws \PDOException
     */
    public function hasEntry(string $iri): bool
    {
        // to avoid IRIs such as https://w3id.org/AIRO and https://w3id.org/airo considered different
        $iri = strtolower($iri);

        $stmt = $this->temporaryIndexDb->prepare('SELECT ontology_iri FROM entry WHERE ontology_iri = ?');
        $stmt->execute([$iri]);
        foreach ($stmt->getIterator() as $entry) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int,\App\IndexEntry> $temporaryIndex
     *
     * @throws \Exception
     * @throws \PDOException
     */
    public function storeEntries(array $temporaryIndex): void
    {
        foreach ($temporaryIndex as $indexEntry) {
            if (false === $indexEntry->isValid()) {
                echo PHP_EOL;
                var_dump($indexEntry);
                throw new Exception('IndexEntry instance is invalid');
            }

            try {
                // build insert query
                // no prepared statements anymore, because they sometimes lead to:
                //      Uncaught PDOException: SQLSTATE[HY000]: General error: 21 bad parameter or other API misuse
                $insertQ = $this->insertIntoQueryHead;
                $insertQ .= '"'.implode('","', [
                    addslashes((string) $indexEntry->getOntologyTitle()),
                    $indexEntry->getOntologyIri(),
                    // general information
                    addslashes((string) $indexEntry->getSummary()),
                    (string) $indexEntry->getLicenseInformation(),
                    addslashes((string) $indexEntry->getAuthors()),
                    addslashes((string) $indexEntry->getContributors()),
                    $indexEntry->getProjectPage(),
                    $indexEntry->getSourcePage(),
                    // files
                    $indexEntry->getLatestJsonLdFile(),
                    $indexEntry->getLatestN3File(),
                    $indexEntry->getLatestNtriplesFile(),
                    $indexEntry->getLatestRdfXmlFile(),
                    $indexEntry->getLatestTurtleFile(),
                    // misc
                    $indexEntry->getModified(),
                    $indexEntry->getVersion(),
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

    /**
     * Attempts to update all changed fields of an entry in the DB.
     *
     * @throws \Exception
     * @throws \PDOException
     */
    public function updateEntry(IndexEntry $entry): void
    {
        $stmt = $this->temporaryIndexDb->prepare('SELECT * FROM entry WHERE ontology_iri = ?');
        $stmt->execute([$entry->getOntologyIri()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (is_array($row)) {
            $sql = 'UPDATE entry SET ';
            $setEntries = [];
            $params = [];

            if ($entry->getOntologyTitle() != $row['ontology_title'] && isEmpty($row['ontology_title'])) {
                $setEntries[] = 'ontology_title = ?';
                $params[] = addslashes((string) $entry->getOntologyTitle());
            }

            if ($entry->getSummary() != $row['summary'] && isEmpty($row['summary'])) {
                $setEntries[] = 'summary = ?';
                $params[] = addslashes((string) $entry->getSummary());
            }

            if ($entry->getLicenseInformation() != $row['license_information'] && isEmpty($row['license_information'])) {
                $setEntries[] = 'license_information = ?';
                $params[] = addslashes((string) $entry->getLicenseInformation());
            }

            if ($entry->getAuthors() != $row['authors'] && isEmpty($row['authors'])) {
                $setEntries[] = 'authors = ?';
                $params[] = addslashes((string) $entry->getAuthors());
            }

            if ($entry->getContributors() != $row['contributors'] && isEmpty($row['contributors'])) {
                $setEntries[] = 'contributors = ?';
                $params[] = addslashes((string) $entry->getContributors());
            }

            if ($entry->getProjectPage() != $row['project_page'] && isEmpty($row['project_page'])) {
                $setEntries[] = 'ontology_title = ?';
                $params[] = addslashes((string) $entry->getProjectPage());
            }

            if ($entry->getSourcePage() != $row['source_page'] && isEmpty($row['source_page'])) {
                $setEntries[] = 'source_page = ?';
                $params[] = addslashes((string) $entry->getSourcePage());
            }

            if ($entry->getLatestJsonLdFile() != $row['latest_json_ld_file'] && isEmpty($row['latest_json_ld_file'])) {
                $setEntries[] = 'latest_json_ld_file = ?';
                $params[] = addslashes((string) $entry->getLatestJsonLdFile());
            }

            if ($entry->getLatestN3File() != $row['latest_n3_file'] && isEmpty($row['latest_n3_file'])) {
                $setEntries[] = 'latest_n3_file = ?';
                $params[] = addslashes((string) $entry->getLatestN3File());
            }

            if ($entry->getLatestNtriplesFile() != $row['latest_ntriples_file'] && isEmpty($row['latest_ntriples_file'])) {
                $setEntries[] = 'latest_ntriples_file = ?';
                $params[] = addslashes((string) $entry->getLatestNtriplesFile());
            }

            if ($entry->getLatestRdfXmlFile() != $row['latest_rdfxml_file'] && isEmpty($row['latest_rdfxml_file'])) {
                $setEntries[] = 'latest_rdfxml_file = ?';
                $params[] = addslashes((string) $entry->getLatestRdfXmlFile());
            }

            if ($entry->getLatestTurtleFile() != $row['latest_turtle_file'] && isEmpty($row['latest_turtle_file'])) {
                $setEntries[] = 'latest_turtle_file = ?';
                $params[] = addslashes((string) $entry->getLatestTurtleFile());
            }

            if ($entry->getModified() != $row['modified'] && isEmpty($row['modified'])) {
                $setEntries[] = 'modified = ?';
                $params[] = addslashes((string) $entry->getModified());
            }

            if ($entry->getVersion() != $row['version'] && isEmpty($row['version'])) {
                $setEntries[] = 'version = ?';
                $params[] = addslashes((string) $entry->getVersion());
            }

            if (0 == count($setEntries)) {
                return;
            }

            $params[] = $entry->getOntologyIri();

            $sql .= implode(', ', $setEntries);
            $sql .= ' WHERE ontology_iri = ?';

            $this->sendUpdateStmt($sql, $params);

        } else {
            throw new Exception('No entry found for '.$entry->getOntologyIri());
        }
    }

    /**
     * @param non-empty-string $sql
     * @param array<string|int|float|null> $param
     *
     * @throws \PDOException
     */
    private function sendUpdateStmt(string $sql, array $param): void
    {
        $stmt = $this->temporaryIndexDb->prepare($sql);
        $stmt->execute($param);
    }

    /**
     * @throws \PDOException
     */
    public function writeToIndexCsv(): void
    {
        $filePath = ROOT_DIR_PATH.'index.csv';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // get a list of all entries
        $sql = 'SELECT ontology_title, ontology_iri,
                       summary, authors, contributors, license_information, project_page, source_page,
                       latest_json_ld_file, latest_n3_file, latest_ntriples_file, latest_rdfxml_file, latest_turtle_file,
                       modified, version, source_title, source_url
                  FROM entry
                 ORDER BY ontology_title ASC';
        $stmt = $this->temporaryIndexDb->prepare($sql);
        $stmt->execute();

        $dataToWrite = INDEX_CSV_HEAD_STRING.PHP_EOL;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var array<string|int,string|null> */
            $row = $row;
            $dataToWrite .= '"'.implode('","', $row).'"'.PHP_EOL;
        }

        file_put_contents($filePath, $dataToWrite);
    }

    /**
     * @throws \PDOException
     */
    public function writeToIndexJsonl(): void
    {
        $filePath = ROOT_DIR_PATH.'index.jsonl';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // get a list of all entries
        $sql = 'SELECT ontology_title, ontology_iri,
                       summary, authors, contributors, license_information, project_page, source_page,
                       latest_json_ld_file, latest_n3_file, latest_ntriples_file, latest_rdfxml_file, latest_turtle_file,
                       modified, version, source_title, source_url
                  FROM entry
                 ORDER BY ontology_title ASC';
        $stmt = $this->temporaryIndexDb->prepare($sql);
        $stmt->execute();

        $dataToWrite = '';
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dataToWrite .= json_encode($row).PHP_EOL;
        }

        file_put_contents($filePath, $dataToWrite);
    }
}
