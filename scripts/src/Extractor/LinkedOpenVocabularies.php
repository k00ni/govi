<?php

declare(strict_types=1);

namespace App\Extractor;

use App\IndexEntry;
use EasyRdf\Graph;
use Exception;
use quickRdfIo\Util;

use function App\isEmpty;
use function App\uncompressGzArchive;

class LinkedOpenVocabularies extends AbstractExtractor
{
    protected string $namespace = 'extractor_linked_open_vocabularies';
    protected string $lovN3Filepath = SCRIPTS_DIR_PATH.'var'.DIRECTORY_SEPARATOR.'lov.n3';
    private string $ontologyListUrl = 'https://lov.linkeddata.es/lov.n3.gz';

    public function run(): void
    {
        echo PHP_EOL;
        echo 'Linked Open Vocabularies::run:';
        echo PHP_EOL;

        $ontologiesToProcess = $this->getOntologiesToProcess();
        echo PHP_EOL;
        echo count($ontologiesToProcess).' ontologies to process';

        // Download latest LOV dump (.gz file) and uncompress it.
        // We assume it contains all vocabulary meta data.
        // TODO if this takes too long, use gunzip for alternatives instead.
        if (file_exists($this->lovN3Filepath)) {
            unlink($this->lovN3Filepath);
        }

        uncompressGzArchive($this->ontologyListUrl, $this->lovN3Filepath);

        echo PHP_EOL;
        echo PHP_EOL;
        echo $this->ontologyListUrl.' downloaded and uncompressed to '.$this->lovN3Filepath;

        // use rapper here, because quickRdfIo doesn't parse lov.n3 here
        $command = 'rapper -i turtle -o ntriples '.$this->lovN3Filepath;
        $nquads = (string) shell_exec($command);
        $iterator = Util::parse($nquads, $this->dataFactory, 'turtle');
        $graph = $this->generateEasyRdfGraphForQuadList($iterator);

        foreach ($ontologiesToProcess as $ontology) {
            echo PHP_EOL;
            echo PHP_EOL.' - process '.$ontology->getOntologyTitle().' >> '.$ontology->getOntologyIri();
            $this->addFurtherMetadata($ontology, $graph);

            // get latest N3 file
            $valuesString = $this->getLiteralValuesAsString($graph, ['dcat:distribution'], $ontology->getOntologyIri());
            $valuesString = $this->cleanString($valuesString);
            $ontology->setLatestN3File($valuesString);

            $this->storeTemporaryIndexIntoSQLiteFile([$ontology]);
        }

        /*
        $ontologyRelatedTriples = [];
        $predicateWhitelist = ['http://purl.org/dc/terms/modified', 'http://www.w3.org/ns/dcat#distribution'];

        // for performance reasons (dump file has over 200k triples), we only
        // collect those which are relevant
        foreach ($iterator as $quad) {
            // found vocabulary entry
            $s = $quad->getSubject()->getValue();
            if (isset($ontologiesToProcess[$s]) && in_array($quad->getPredicate()->getValue(), $predicateWhitelist, true)) {
                // determine object type
                if ($quad->getObject() instanceof LiteralInterface) {
                    $oType = 'uri';
                } elseif ($quad->getObject() instanceof BlankNodeInterface) {
                    $oType = 'bnode';
                } else {
                    $oType = 'literal';
                }

                $ontologyRelatedTriples[] = [
                    's' => $quad->getSubject()->getValue(),
                    's_type' => $quad->getSubject() instanceof BlankNodeInterface ? 'bnnode' : 'uri',
                    'p' => $quad->getPredicate()->getValue(),
                    'p_type' => $quad->getPredicate() instanceof BlankNodeInterface ? 'bnnode' : 'uri',
                    'o' => $quad->getObject()->getValue(),
                    'o_type' => $oType,
                    'o_lang' => $quad->getObject() instanceof LiteralInterface ? $quad->getObject()->getLang() : '',
                    'o_datatype' => $quad->getObject() instanceof LiteralInterface ? $quad->getObject()->getDatatype() : '',
                ];
            }
        }
        */
    }

    public function getPreparedIndexEntry(): IndexEntry
    {
        return new IndexEntry('Linked Open Vocabularies', $this->ontologyListUrl);
    }

    /**
     * @return list<\App\IndexEntry>
     */
    public function getOntologiesToProcess(): array
    {
        $url = 'https://lov.linkeddata.es/dataset/lov/api/v2/vocabulary/list';
        $json = file_get_contents($url);
        $entriesArr = json_decode($json, true);
        $result = [];

        foreach ($entriesArr as $arr) {
            $entry = $this->getPreparedIndexEntry();

            $title = $this->cleanString($arr['titles'][0]['value'], false);
            $entry->setOntologyTitle($title); // TODO: handle case: multiple title entries

            $entry->setontologyIri($arr['uri']);

            $result[$arr['uri']] = $entry;
        }

        return $result;
    }
}
