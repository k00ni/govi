<?php

namespace App;

class IndexEntry
{
    private ?string $ontologyTitle;
    private ?string $ontologyUri;
    private ?string $latestNtFile;
    private ?string $latestOwlFile;
    private ?string $latestTtlFile;
    private string $sourceTitle;
    private string $sourceUrl;

    public function __construct(string $sourceTitle, string $sourceUrl)
    {
        $this->sourceTitle = $sourceTitle;
        $this->sourceUrl = $sourceUrl;
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
            $this->latestOwlFile,
            $this->latestTtlFile,
            $this->sourceTitle,
            $this->sourceUrl,
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

    public function getLatestOwlFile(): ?string
    {
        return $this->latestOwlFile;
    }

    public function setLatestOwlFile(string $latestOwlFile): self
    {
        $this->latestOwlFile = $latestOwlFile;

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
