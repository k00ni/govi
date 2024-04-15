<?php

// paths
define('ROOT_DIR_PATH', __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
define('SCRIPTS_DIR_PATH', ROOT_DIR_PATH.'scripts'.DIRECTORY_SEPARATOR);

// CSV
define(
    'INDEX_CSV_HEAD_STRING',
    '"ontology title","ontology iri","summary","authors","contributors","license information","project page","source page","latest json-ld file","latest n3 file","latest ntriples file","latest rdf/xml file","latest turtle file","latest access","source title","source url"'
);

define('MANUALLY_MAINTAINED_METADATA_ABOUT_ONTOLOGIES_CSV', 'manually-maintained-metadata-about-ontologies.csv');

define('SQLITE_FILE_PATH', SCRIPTS_DIR_PATH.'var'.DIRECTORY_SEPARATOR.'temporary-index.db');

// properties usually used to determine a title
$titleProperties = [
    'http://www.w3.org/2004/02/skos/core#prefLabel',
    'http://purl.org/dc/elements/1.1/title',
    'http://purl.org/dc/terms/title',
    'http://www.w3.org/2000/01/rdf-schema#label',
    'http://xmlns.com/foaf/0.1/name',
];

$bioPortalApiKeyFile = ROOT_DIR_PATH.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'.api_key_bioportal.php';

// include vendor libraries
require_once SCRIPTS_DIR_PATH.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
