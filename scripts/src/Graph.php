<?php

declare(strict_types=1);

namespace App;

use Countable;
use EasyRdf\RdfNamespace;
use Exception;
use rdfInterface\BlankNodeInterface;
use rdfInterface\LiteralInterface;
use rdfInterface\NamedNodeInterface;

class Graph implements Countable
{
    /**
     * @var array<\rdfInterface\QuadInterface>
     */
    private array $list;

    /**
     * @param array<\rdfInterface\QuadInterface> $list
     */
    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function count(): int
    {
        return count($this->list);
    }

    public function getLabel(string $subjectUri, string|null $lang = null): string|null
    {
        $labelProperties = [
            'http://www.w3.org/2004/02/skos/core#prefLabel',
            'http://www.w3.org/2000/01/rdf-schema#label',
            'http://xmlns.com/foaf/0.1/name',
            'http://purl.org/dc/terms/title',
            'http://purl.org/dc/elements/1.1/title',
        ];

        foreach ($this->list as $quad) {
            if (
                $quad->getSubject()->getValue() == $subjectUri
                && in_array($quad->getPredicate()->getValue(), $labelProperties, true)
            ) {
                if (
                    null === $lang
                    || ($quad->getObject() instanceof LiteralInterface && $quad->getObject()->getLang() == $lang)
                ) {
                    return $quad->getObject()->getValue();
                }
            }
        }

        return null;
    }

    /**
     * @return array<string>
     *
     * @throws \InvalidArgumentException
     */
    public function getPropertyValues(string $subjectUri, string $propertyUri, string|null $lang = null): array
    {
        $result = [];
        $subjectUri = RdfNamespace::expand($subjectUri);
        $propertyUri = RdfNamespace::expand($propertyUri);

        foreach ($this->list as $quad) {
            if (
                $quad->getSubject()->getValue() == $subjectUri
                && $quad->getPredicate()->getValue() == $propertyUri
                && (
                    null == $lang
                    || (
                        $quad->getObject() instanceof LiteralInterface
                        && $quad->getObject()->getLang() == $lang
                    )
                )
            ) {
                $result[] = $quad->getObject()->getValue();
            }
        }

        return $result;
    }

    /**
     * @return array<mixed>
     *
     * @throws \Exception
     */
    public function getSimplifiedPropertyObjectsList(string $subjectUri): array
    {
        $result = [];

        foreach ($this->list as $quad) {
            if ($quad->getSubject()->getValue() == $subjectUri) {
                if (false === isset($result[$quad->getPredicate()->getValue()])) {
                    $result[$quad->getPredicate()->getValue()] = [];
                }

                if ($quad->getObject() instanceof LiteralInterface) {
                    $obj = [
                        'type' => 'literal',
                        'value' => $quad->getObject()->getValue(),
                        'lang' => $quad->getObject()->getLang(),
                        'data_type' => $quad->getObject()->getDatatype(),
                    ];
                } elseif ($quad->getObject() instanceof NamedNodeInterface) {
                    $obj = [
                        'type' => 'uri',
                        'value' => $quad->getObject()->getValue(),
                    ];
                } elseif ($quad->getObject() instanceof BlankNodeInterface) {
                    $obj = [
                        'type' => 'blanknode',
                        'value' => $quad->getObject()->getValue(),
                    ];
                } else {
                    throw new Exception('Invalid object type');
                }

                $result[$quad->getPredicate()->getValue()][] = $obj;
            }
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function hasSubject(string $uri): bool
    {
        $fullUri = RdfNamespace::expand($uri);

        foreach ($this->list as $quad) {
            if ($quad->getSubject()->getValue() == $fullUri) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws \InvalidArgumentException
     *
     * @return array<non-empty-string>
     */
    public function getInstancesOfType(string $type): array
    {
        $fullTypeUri = RdfNamespace::expand($type);

        $result = [];

        foreach ($this->list as $quad) {
            if (
                $quad->getPredicate()->getValue() == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
                && $quad->getObject()->getValue() == $fullTypeUri
            ) {
                /** @var non-empty-string */
                $val = $quad->getSubject()->getValue();
                $result[] = $val;
            }
        }

        return $result;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function hasInstancesOfType(string $type): bool
    {
        $fullTypeUri = RdfNamespace::expand($type);

        foreach ($this->list as $quad) {
            if (
                $quad->getPredicate()->getValue() == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
                && $quad->getObject()->getValue() == $fullTypeUri
            ) {
                return true;
            }
        }

        return false;
    }
}
