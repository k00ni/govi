<?php

declare(strict_types=1);

namespace App;

use DateTime;
use DateTimeZone;
use Exception;

class IndexEntry
{
    private ?string $ontologyTitle = null;
    private ?string $ontologyIri = null;
    private ?string $latestN3File = null;
    private ?string $latestNtFile = null;
    private ?string $latestRdfXmlFile = null;
    private ?string $latestTtlFile = null;

    /**
     * Date Time of the access in format Y-m-d H:i:s
     */
    private string $latestAccess;
    private string $sourceTitle;
    private string $sourceUrl;

    public function __construct(string $sourceTitle, string $sourceUrl)
    {
        $this->sourceTitle = $sourceTitle;
        $this->sourceUrl = $sourceUrl;

        // only date of the latest access
        $now = (new DateTime('now', new DateTimeZone('UTC')));
        $this->latestAccess = $now->format('Y-m-d').' 00:00:00';
    }

    public function getOntologyTitle(): ?string
    {
        return $this->ontologyTitle;
    }

    public function setOntologyTitle(string $ontologyTitle): self
    {
        $this->ontologyTitle = trim($ontologyTitle);

        return $this;
    }

    public function getOntologyIri(): ?string
    {
        return $this->ontologyIri;
    }

    public function setOntologyIri(string $ontologyIri): self
    {
        $this->ontologyIri = trim($ontologyIri);
        return $this;
    }

    public function getLatestAccess(): string
    {
        return $this->latestAccess;
    }

    public function setLatestAccess(string $latestAccess): self
    {
        $this->latestAccess = trim($latestAccess);

        return $this;
    }

    public function getLatestN3File(): ?string
    {
        return $this->latestN3File;
    }

    /**
     * @throws \Exception if latestN3File is nota valid URL.
     */
    public function setLatestN3File(string $latestN3File): self
    {
        if (isUrl($latestN3File) || isEmpty($latestN3File)) {
            $this->latestN3File = trim($latestN3File);

            return $this;
        } else {
            throw new Exception($latestN3File.' is not a valid URL');
        }
    }

    public function getLatestNtFile(): ?string
    {
        return $this->latestNtFile;
    }

    /**
     * @throws \Exception if latestNtFile is nota valid URL.
     */
    public function setLatestNtFile(string $latestNtFile): self
    {
        if (isUrl($latestNtFile) || isEmpty($latestNtFile)) {
            $this->latestNtFile = trim($latestNtFile);

            return $this;
        } else {
            throw new Exception($latestNtFile.' is not a valid URL');
        }
    }

    public function getLatestRdfXmlFile(): ?string
    {
        return $this->latestRdfXmlFile;
    }

    /**
     * @throws \Exception if latestRdfXmlFile is nota valid URL.
     */
    public function setLatestRdfXmlFile(string $latestRdfXmlFile): self
    {
        if (isUrl($latestRdfXmlFile) || isEmpty($latestRdfXmlFile)) {
            $this->latestRdfXmlFile = trim($latestRdfXmlFile);
            return $this;
        } else {
            throw new Exception($latestRdfXmlFile.' is not a valid URL');
        }
    }

    public function getLatestTtlFile(): ?string
    {
        return $this->latestTtlFile;
    }

    /**
     * @throws \Exception if latestTtlFile is nota valid URL.
     */
    public function setLatestTtlFile(string $latestTtlFile): self
    {
        if (isUrl($latestTtlFile) || isEmpty($latestTtlFile)) {
            $this->latestTtlFile = trim($latestTtlFile);

            return $this;
        } else {
            throw new Exception($latestTtlFile.' is not a valid URL');
        }
    }

    public function getSourceTitle(): string
    {
        return $this->sourceTitle;
    }

    public function getSourceUrl(): string
    {
        return $this->sourceUrl;
    }
}
