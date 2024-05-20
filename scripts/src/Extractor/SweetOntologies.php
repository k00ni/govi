<?php

declare(strict_types=1);

namespace App\Extractor;

use App\IndexEntry;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

/**
 * Semantic Web for Earth and Environmental Terminology (SWEET) Ontologies
 *
 * Reads ontology information from Githhub repository:
 *
 * https://github.com/ESIPFed/sweet
 */
class SweetOntologies extends AbstractExtractor
{
    private string $nameOfUnzippedFolder = 'sweet-master';

    protected string $namespace = 'extractor_sweet_ontologies';

    /**
     * Folder path to ontology list.
     */
    protected string $unzippedContent = VAR_FOLDER_PATH.'sweet-master'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR;
    private string $ontologyListUrl = 'https://github.com/ESIPFed/sweet/archive/refs/heads/master.zip';

    /**
     * @throws \Exception
     * @throws \UnexpectedValueException
     */
    public function run(): void
    {
        echo PHP_EOL;
        echo '-------------------------------------------------';
        echo PHP_EOL;
        echo 'Sweet Ontologies - Extraction started ...';
        echo PHP_EOL;

        /*
         * Approach:
         *
         * 1. download latest state of the repository
         * 2. unzip
         * 3. read content of src folder (contains all ontologies)
         * 4. fill index
         */

        // 1. download latest state of the repository
        $downloadedFilepath = $this->cache->getCachedFilePathForFileUrl($this->ontologyListUrl);

        // if available, remove folder with repository content
        if (file_exists(VAR_FOLDER_PATH.$this->nameOfUnzippedFolder)) {
            $this->removeFolderRec(VAR_FOLDER_PATH.$this->nameOfUnzippedFolder);
        }

        // 2. unzip
        $this->unzip($downloadedFilepath, VAR_FOLDER_PATH);

        // 3. read content of src folder to get a list of all ontology files
        $path = VAR_FOLDER_PATH.$this->nameOfUnzippedFolder.DIRECTORY_SEPARATOR.'src';
        $ttlFiles = $this->getTtlFileListOfFolder($path);

        sort($ttlFiles);

        foreach ($ttlFiles as $ttlFilepath) {
            echo PHP_EOL;
            echo PHP_EOL.'process: '.$ttlFilepath;
            echo PHP_EOL;

            $newEntry = $this->getPreparedIndexEntry();
            $fileHandle = fopen($ttlFilepath, 'r');
            if (false === $fileHandle) {
                throw new Exception('Could not read file '.$ttlFilepath);
            }

            $graph = $this->loadQuadsIntoGraph($fileHandle, $ttlFilepath);

            // get ontology IRI
            $entries = $graph->getInstancesOfType('owl:Ontology');
            if (1 == count($entries)) {
                $newEntry->setOntologyIri($entries[0]);
                $this->addFurtherMetadata($newEntry, $graph);
            } else {
                echo PHP_EOL.'IGNORED, because none or more than 1 ontologies found in '.$ttlFilepath;
                continue;
            }

            // set title
            $newEntry->setOntologyTitle($graph->getLabel((string) $newEntry->getOntologyIri()));

            // build TTL file URL and save it
            $newEntry->setLatestTurtleFile('https://raw.githubusercontent.com/ESIPFed/sweet/master/src/'.basename($ttlFilepath));

            // set modified date
            $lastChangeTimestamp = filemtime($ttlFilepath);
            if (0 < $lastChangeTimestamp) {
                $newEntry->setModified(date('Y-m-d', $lastChangeTimestamp));
            }

            // 4. fill index
            $this->temporaryIndex->storeEntries([$newEntry]);
        }
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('Sweet Ontologies', $this->ontologyListUrl);
    }

    /**
     * @return array<non-empty-string>
     *
     * @throws \UnexpectedValueException
     */
    private function getTtlFileListOfFolder(string $folderpath): array
    {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderpath));
        $files = [];

        /** @var \SplFileInfo $file */
        foreach ($rii as $file) {
            if ($file->isDir()) {
                continue;
            }

            if (str_contains($file->getPathname(), '.ttl')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * @param non-empty-string $fileToExtract
     * @param non-empty-string $targetPath
     *
     * @throws \Exception
     */
    private function unzip(string $fileToExtract, string $targetPath): void
    {
        $zip = new ZipArchive();
        $res = $zip->open($fileToExtract);
        if ($res === true) {
            $zip->extractTo($targetPath);
            $zip->close();
        } else {
            throw new Exception('Unzip of '.$fileToExtract.' failed!');
        }
    }

    /**
     * @throws \UnexpectedValueException
     */
    private function removeFolderRec(string $folderpath): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderpath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($folderpath);
    }
}
