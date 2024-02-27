<?php

namespace App;

use DateTime;
use DateTimeZone;

class IndexEntry
{
    private ?string $ontologyTitle;
    private ?string $ontologyUri;
    private ?string $latestNtFile;
    private ?string $latestRdfXmlFile;
    private ?string $latestTtlFile;

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

    /**
     * Helper function to transform this instance into an array (required for CSV file generation).
     *
     * @return array<string|null>
     */
    public function __toArray(): array
    {
        return [
            $this->ontologyTitle,
            $this->ontologyUri,
            $this->latestNtFile,
            $this->latestRdfXmlFile,
            $this->latestTtlFile,
            $this->sourceTitle,
            $this->sourceUrl,
            $this->latestAccess,
        ];
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

    public function setOntologyUri(string $ontologyUri): self
    {
        $this->ontologyUri = $ontologyUri;

        return $this;
    }

    public function getLatestNtFile(): ?string
    {
        return $this->latestNtFile;
    }

    public function setLatestNtFile(string $latestNtFile): self
    {
        $this->latestNtFile = $latestNtFile;

        return $this;
    }

    public function getLatestRdfXmlFile(): ?string
    {
        return $this->latestRdfXmlFile;
    }

    public function setLatestRdfXmlFile(string $latestRdfXmlFile): self
    {
        $this->latestRdfXmlFile = $latestRdfXmlFile;

        return $this;
    }

    public function getLatestTtlFile(): ?string
    {
        return $this->latestTtlFile;
    }

    public function setLatestTtlFile(string $latestTtlFile): self
    {
        $this->latestTtlFile = $latestTtlFile;

        return $this;
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
