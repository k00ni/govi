<?php

declare(strict_types=1);

namespace App;

use Exception;
use PDO;
use PDOException;

class TemporaryIndex
{
    /**
     * @var non-empty-string
     */
    private string $insertIntoQueryHead = 'INSERT INTO entry (
        ontology_iri,
        ontology_title,
        summary,
        license_information,
        authors,
        contributors,
        project_page,
        source_page,
        latest_json_ld_file,
        latest_n3_file,
        latest_ntriples_file,
        latest_rdfxml_file,
        latest_turtle_file,
        latest_access,
        source_title,
        source_url
    ) VALUES (';

    protected PDO $temporaryIndexDb;

    /**
     * @throws \PDOException
     */
    public function __construct()
    {
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
            source_page TEXT,
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
     * @return non-empty-string
     */
    public function getInsertIntoQueryHead(): string
    {
        return $this->insertIntoQueryHead;
    }

    /**
     * @throws \PDOException
     */
    public function hasEntry(string $iri): bool
    {
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
                    $indexEntry->getOntologyIri(),
                    addslashes((string) $indexEntry->getOntologyTitle()),
                    addslashes((string) $indexEntry->getSummary()),
                    (string) $indexEntry->getLicenseInformation(),
                    addslashes((string) $indexEntry->getAuthors()),
                    addslashes((string) $indexEntry->getContributors()),
                    $indexEntry->getProjectPage(),
                    $indexEntry->getSourcePage(),
                    // files
                    $indexEntry->getLatestJsonLdFile(),
                    $indexEntry->getLatestN3File(),
                    $indexEntry->getLatestNtFile(),
                    $indexEntry->getLatestRdfXmlFile(),
                    $indexEntry->getLatestTurtleFile(),
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

    /**
     * @throws \PDOException
     */
    public function writeToIndexCsv(): void
    {
        $csvPath = ROOT_DIR_PATH.'index.csv';

        if (file_exists($csvPath)) {
            unlink($csvPath);
        }

        // get a list of all entries
        $sql = 'SELECT ontology_title, ontology_iri,
                       summary, authors, contributors, license_information, project_page, source_page,
                       latest_json_ld_file, latest_n3_file, latest_ntriples_file, latest_rdfxml_file, latest_turtle_file,
                       latest_access, source_title, source_url
                  FROM entry
                 ORDER BY ontology_title ASC';
        $stmt = $this->temporaryIndexDb->prepare($sql);
        $stmt->execute();

        $dataToWrite = INDEX_CSV_HEAD_STRING.PHP_EOL;
        $i = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            /** @var array<string|int,string|null> */
            $row = $row;

            echo '.';

            $dataToWrite .= '"'.implode('","', $row).'"'.PHP_EOL;

            ++$i;
        }

        file_put_contents($csvPath, $dataToWrite);
    }
}
