<?php

declare(strict_types=1);

namespace App;

use DateTime;
use DateTimeZone;
use Exception;

class IndexEntry
{
    private ?string $ontologyTitle;
    private ?string $ontologyUri;
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

        $this->latestAccess = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    public function getOntologyTitle(): ?string
    {
        return $this->ontologyTitle;
    }

    public function setOntologyTitle(string $ontologyTitle): self
    {
        $this->ontologyTitle = $ontologyTitle;

        return $this;
    }

    public function getOntologyUri(): ?string
    {
        return $this->ontologyUri;
    }

    /**
     * @throws \Exception if ontologyUri is nota valid URL.
     */
    public function setOntologyUri(string $ontologyUri): self
    {
        if (isUrl($ontologyUri)) {
            $this->ontologyUri = $ontologyUri;

            return $this;
        } else {
            throw new Exception($ontologyUri.' is not a valid URL');
        }
    }

    public function getLatestAccess(): string
    {
        return $this->latestAccess;
    }

    public function setLatestAccess(string $latestAccess): self
    {
        $this->latestAccess = $latestAccess;

        return $this;
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
            $this->latestNtFile = $latestNtFile;

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
            $this->latestRdfXmlFile = $latestRdfXmlFile;
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
            $this->latestTtlFile = $latestTtlFile;

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
