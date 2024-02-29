<?php

// paths
define('ROOT_DIR_PATH', __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
define('INDEX_CSV_PATH', ROOT_DIR_PATH.'index.csv');

// CSV
define(
    'INDEX_CSV_HEAD_STRING',
    '"ontology title","ontology uri","latest ntriples file","latest rdf/xml file","latest turtle file","latest access","source title","source url"'
);

define('SQLITE_FILE_PATH', ROOT_DIR_PATH.'var'.DIRECTORY_SEPARATOR.'temporary-index.db');

// include vendor libraries
require ROOT_DIR_PATH.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
