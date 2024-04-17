<?php

namespace Tests;

use App\Graph;
use quickRdf\DataFactory;
use Test\TestCase;

class GraphTest extends TestCase
{
    private function getTestData(): Graph
    {
        $df = new DataFactory();

        return new Graph([
            $df->quad(
                $df->namedNode('http://foo'),
                $df->namedNode('http://bar'),
                $df->namedNode('http://baz'),
            ),
            $df->quad(
                $df->namedNode('http://foo'),
                $df->namedNode('http://www.w3.org/ns/dcat#distribution'),
                $df->namedNode('http://link'),
            ),
            $df->quad(
                $df->namedNode('http://foo'),
                $df->namedNode('http://tttt/'),
                $df->literal('1.0'),
            ),
            $df->quad(
                $df->namedNode('http://foo'),
                $df->namedNode('http://tttt/'),
                $df->literal('in en', 'en'),
            ),
            $df->quad(
                $df->namedNode('http://concept1'),
                $df->namedNode('http://www.w3.org/1999/02/22-rdf-syntax-ns#type'),
                $df->namedNode('http://www.w3.org/2004/02/skos/core#Concept'),
            ),
        ]);
    }

    public function testGetPropertyValues(): void
    {
        $graph = $this->getTestData();

        $this->assertEquals(
            ['http://baz'],
            $graph->getPropertyValues('http://foo', 'http://bar')
        );
    }

    public function testGetPropertyPrefixedUrl(): void
    {
        $graph = $this->getTestData();

        $this->assertEquals(
            ['http://link'],
            $graph->getPropertyValues('http://foo', 'dcat:distribution')
        );
    }

    public function testGetPropertyValuesLang(): void
    {
        $graph = $this->getTestData();

        $this->assertEquals(
            ['in en'],
            $graph->getPropertyValues('http://foo', 'http://tttt/', 'en')
        );
    }

    public function testHasInstancesOfType(): void
    {
        $graph = $this->getTestData();

        $this->assertTrue($graph->hasInstancesOfType('skos:Concept'));
        $this->assertTrue($graph->hasInstancesOfType('http://www.w3.org/2004/02/skos/core#Concept'));
    }
}
