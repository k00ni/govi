<?php

namespace Tests;

use App\IndexEntry;
use App\TemporaryIndex;
use Test\TestCase;

class TemporaryIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (file_exists(VAR_FOLDER_PATH.'test.db')) {
            unlink(VAR_FOLDER_PATH.'test.db');
        }
    }

    public function testUpdateEntry(): void
    {
        $subjectUnderTest = new TemporaryIndex(VAR_FOLDER_PATH.'test.db');

        $newEntry = new IndexEntry('test1', 'test2');
        $newEntry->setLatestJsonLdFile('http://localhost/test.ttl');
        $newEntry->setOntologyIri('http://localhost/');
        $newEntry->setOntologyTitle('test onto');

        $subjectUnderTest->storeEntries([$newEntry]);

        // check that entry is in DB
        $this->assertTrue($subjectUnderTest->hasEntry($newEntry->getOntologyIri()));

        // update entry in the meantime
        $newEntry->setLicenseInformation('test license');

        // update entry in DB
        $subjectUnderTest->updateEntry($newEntry);

        $entryArr = $subjectUnderTest->getEntryDataAsArray($newEntry->getOntologyIri());

        $this->assertEquals($newEntry->getLicenseInformation(), $entryArr['license_information']);
    }

    public function testUpdateEntryKeepExistingValues(): void
    {
        $subjectUnderTest = new TemporaryIndex(VAR_FOLDER_PATH.'test.db');

        $newEntry = new IndexEntry('test1', 'test2');
        $newEntry->setLatestJsonLdFile('http://localhost/test.ttl');
        $newEntry->setOntologyIri('http://localhost/');
        $newEntry->setOntologyTitle('test onto');
        $newEntry->setVersion('test version');

        $subjectUnderTest->storeEntries([$newEntry]);

        // check that entry is in DB
        $this->assertTrue($subjectUnderTest->hasEntry($newEntry->getOntologyIri()));

        // update entry in the meantime
        $newEntry->setVersion('CHANGED version');

        // update entry in DB
        $subjectUnderTest->updateEntry($newEntry);

        $entryArr = $subjectUnderTest->getEntryDataAsArray($newEntry->getOntologyIri());

        $this->assertEquals('test version', $entryArr['version']);
    }
}
