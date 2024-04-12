<?php

declare(strict_types=1);

namespace App;

use DateTime;
use DateTimeZone;
use Exception;

class IndexEntry
{
    private string|null $ontologyTitle = null;
    private string|null $ontologyIri = null;
    private string|null $summary = null;
    private string|null $licenseInformation = null;
    private string|null $authors = null;
    private string|null $contributors = null;
    private string|null $projectPage = null;

    /**
     * The dedicated page on data source, such as:
     * - https://archivo.dbpedia.org/info?o=http://ns.inria.fr/munc#
     * - https://bioportal.bioontology.org/ontologies/ICF
     */
    private string|null $sourcePageUrl = null;

    private string|null $latestN3File = null;
    private string|null $latestNtFile = null;
    private string|null $latestRdfXmlFile = null;
    private string|null $latestJsonLdFile = null;
    private string|null $latestTtlFile = null;

    /**
     * Date of the latest access in format Y-m-d
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

    public function getOntologyTitle(): string|null
    {
        return $this->ontologyTitle;
    }

    public function setOntologyTitle(string $ontologyTitle): self
    {
        $this->ontologyTitle = trim($ontologyTitle);

        return $this;
    }

    public function getOntologyIri(): string|null
    {
        return $this->ontologyIri;
    }

    public function setOntologyIri(string $ontologyIri): self
    {
        $this->ontologyIri = trim($ontologyIri);

        return $this;
    }

    public function getSummary(): string|null
    {
        return $this->summary;
    }

    public function setSummary(string|null $summary): self
    {
        $this->summary = trim($summary);

        return $this;
    }

    public function getLicenseInformation(): string|null
    {
        return $this->licenseInformation;
    }

    public function setLicenseInformation(string|null $licenseInformation): self
    {
        $this->licenseInformation = trim($licenseInformation);

        return $this;
    }

    public function getAuthors(): string|null
    {
        return $this->authors;
    }

    public function setAuthors(string|null $authors): self
    {
        $this->authors = trim($authors);

        return $this;
    }

    public function getContributors(): string|null
    {
        return $this->contributors;
    }

    public function setContributors(string|null $contributors): self
    {
        $this->contributors = trim($contributors);

        return $this;
    }

    public function getProjectPage(): string|null
    {
        return $this->projectPage;
    }

    public function setProjectPage(string|null $projectPage): self
    {
        $this->projectPage = trim($projectPage);

        return $this;
    }

    public function getSourcePageUrl(): string|null
    {
        return $this->sourcePageUrl;
    }

    public function setSourcePageUrl(string|null $sourcePageUrl): self
    {
        $this->sourcePageUrl = trim($sourcePageUrl);

        return $this;
    }

    public function getLatestAccess(): string
    {
        return $this->latestAccess;
    }

    public function setLatestAccess(string $latestAccess): self
    {
        // only save YYYY-MM-DD
        $latestAccess = substr($latestAccess, 0, 10);
        $this->latestAccess = trim($latestAccess);

        return $this;
    }

    public function getLatestN3File(): string|null
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

    public function getLatestNtFile(): string|null
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

    public function getLatestRdfXmlFile(): string|null
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

    public function getLatestTtlFile(): string|null
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

    public function getLatestJsonLdFile(): string|null
    {
        return $this->latestJsonLdFile;
    }

    /**
     * @throws \Exception if latestJsonLdFile is nota valid URL.
     */
    public function setLatestJsonLdFile(string|null $latestJsonLdFile)
    {
        if (isUrl($latestJsonLdFile) || isEmpty($latestJsonLdFile)) {
            $this->latestJsonLdFile = trim($latestJsonLdFile);

            return $this;
        } else {
            throw new Exception($latestJsonLdFile.' is not a valid URL');
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
