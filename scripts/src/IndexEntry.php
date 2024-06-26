<?php

declare(strict_types=1);

namespace App;

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
    private string|null $sourcePage = null;

    private string|null $latestJsonLdFile = null;
    private string|null $latestN3File = null;
    private string|null $latestNtriplesFile = null;
    private string|null $latestRdfXmlFile = null;
    private string|null $latestTurtleFile = null;

    /**
     * Date of the latest modification.
     */
    private string|null $modified = null;

    private string|null $version = null;

    private string $sourceTitle;
    private string $sourceUrl;

    public function __construct(string $sourceTitle, string $sourceUrl)
    {
        $this->sourceTitle = $sourceTitle;
        $this->sourceUrl = $sourceUrl;
    }

    public function getOntologyTitle(): string|null
    {
        return $this->ontologyTitle;
    }

    /**
     * @throws \Exception
     */
    public function setOntologyTitle(string|null $ontologyTitle): self
    {
        if (false === isEmpty($ontologyTitle)) {
            $this->ontologyTitle = trim((string) $ontologyTitle);

            return $this;
        } else {
            throw new Exception('Ontology title can not be empty');
        }
    }

    public function getOntologyIri(): string|null
    {
        return $this->ontologyIri;
    }

    /**
     * @throws \Exception
     */
    public function setOntologyIri(string|null $ontologyIri): self
    {
        if (false === isEmpty($ontologyIri)) {
            $this->ontologyIri = trim((string) $ontologyIri);

            return $this;
        } else {
            throw new Exception('Ontology IRI can not be empty');
        }
    }

    public function getSummary(): string|null
    {
        return $this->summary;
    }

    public function setSummary(string|null $summary): self
    {
        $this->summary = trim((string) $summary);

        return $this;
    }

    public function getLicenseInformation(): string|null
    {
        return $this->licenseInformation;
    }

    public function setLicenseInformation(string|null $licenseInformation): self
    {
        $this->licenseInformation = trim((string) $licenseInformation);

        return $this;
    }

    public function getAuthors(): string|null
    {
        return $this->authors;
    }

    public function setAuthors(string|null $authors): self
    {
        $this->authors = trim((string) $authors);

        return $this;
    }

    public function getContributors(): string|null
    {
        return $this->contributors;
    }

    public function setContributors(string|null $contributors): self
    {
        $this->contributors = trim((string) $contributors);

        return $this;
    }

    public function getProjectPage(): string|null
    {
        return $this->projectPage;
    }

    public function setProjectPage(string|null $projectPage): self
    {
        $this->projectPage = trim((string) $projectPage);

        return $this;
    }

    public function getSourcePage(): string|null
    {
        return $this->sourcePage;
    }

    public function setSourcePage(string|null $sourcePage): self
    {
        $this->sourcePage = trim((string) $sourcePage);

        return $this;
    }

    public function getLatestN3File(): string|null
    {
        return $this->latestN3File;
    }

    /**
     * @throws \Exception if latestN3File is nota valid URL.
     */
    public function setLatestN3File(string|null $latestN3File): self
    {
        if (isUrl($latestN3File) || isEmpty($latestN3File)) {
            $this->latestN3File = trim((string) $latestN3File);

            return $this;
        } else {
            throw new Exception($latestN3File.' is not a valid URL');
        }
    }

    public function getLatestNtriplesFile(): string|null
    {
        return $this->latestNtriplesFile;
    }

    /**
     * @throws \Exception if latestNtriplesFile is nota valid URL.
     */
    public function setLatestNtriplesFile(string|null $latestNtriplesFile): self
    {
        if (isUrl($latestNtriplesFile) || isEmpty($latestNtriplesFile)) {
            $this->latestNtriplesFile = trim((string) $latestNtriplesFile);

            return $this;
        } else {
            throw new Exception($latestNtriplesFile.' is not a valid URL');
        }
    }

    public function getLatestRdfXmlFile(): string|null
    {
        return $this->latestRdfXmlFile;
    }

    /**
     * @throws \Exception if latestRdfXmlFile is nota valid URL.
     */
    public function setLatestRdfXmlFile(string|null $latestRdfXmlFile): self
    {
        if (isUrl($latestRdfXmlFile) || isEmpty($latestRdfXmlFile)) {
            $this->latestRdfXmlFile = trim((string) $latestRdfXmlFile);
            return $this;
        } else {
            throw new Exception($latestRdfXmlFile.' is not a valid URL');
        }
    }

    public function getLatestTurtleFile(): string|null
    {
        return $this->latestTurtleFile;
    }

    /**
     * @throws \Exception if latestTurtleFile is nota valid URL.
     */
    public function setLatestTurtleFile(string|null $latestTurtleFile): self
    {
        if (isUrl($latestTurtleFile) || isEmpty($latestTurtleFile)) {
            $this->latestTurtleFile = trim((string) $latestTurtleFile);

            return $this;
        } else {
            throw new Exception($latestTurtleFile.' is not a valid URL');
        }
    }

    public function getLatestJsonLdFile(): string|null
    {
        return $this->latestJsonLdFile;
    }

    /**
     * @throws \Exception if latestJsonLdFile is nota valid URL.
     */
    public function setLatestJsonLdFile(string|null $latestJsonLdFile): self
    {
        if (isUrl($latestJsonLdFile) || isEmpty($latestJsonLdFile)) {
            $this->latestJsonLdFile = trim((string) $latestJsonLdFile);

            return $this;
        } else {
            throw new Exception($latestJsonLdFile.' is not a valid URL');
        }
    }

    public function getModified(): string|null
    {
        return $this->modified;
    }

    public function setModified(string|null $modified): self
    {
        // only save YYYY-MM-DD
        $modified = substr((string) $modified, 0, 10);
        $this->modified = trim($modified);

        return $this;
    }

    public function getVersion(): string|null
    {
        return $this->version;
    }

    public function setVersion(string|null $version): self
    {
        $this->version = $version;

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

    public function isValid(): bool
    {
        if (
            isEmpty($this->getOntologyTitle())
            || isEmpty($this->getOntologyIri())
            || (
                isEmpty($this->getLatestJsonLdFile())
                && isEmpty($this->getLatestN3File())
                && isEmpty($this->getLatestNtriplesFile())
                && isEmpty($this->getLatestRdfXmlFile())
                && isEmpty($this->getLatestTurtleFile())
            )
        ) {
            return false;
        }

        return true;
    }
}
