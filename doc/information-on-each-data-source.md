# Information on each data source

## General

* if no modified information was found, it tries created related properties

## BioPortal

https://data.bioontology.org/documentation

Related script: [scripts/src/Extractor/BioPortal.php](./../scripts/src/Extractor/BioPortal.php)

**Notes:**
* scripts tries frontend view of ontology first to get a RDF file, if no luck, it tries `ontology.links.download` (such as https://data.bioontology.org/ontologies/INFRARISK/download), because they come in non-RDF formats (e.g. obo)
* further work is required to get more out of BioPortal (e.g. uncompress archives containing RDF data and check them)

## DBpedia Archivo

https://archivo.dbpedia.org/list

Related script: [scripts/src/Extractor/DBpediaArchivo.php](./../scripts/src/Extractor/DBpediaArchivo.php)

**Notes:**
* Used value of "Latest Timestamp" for latest access, "2020.06.10-175249" is interpreted as "2020-06-10 00:00:00"

## Linked Open Vocabularies (LOV)

https://lov.linkeddata.es/dataset/lov/

Related script: [scripts/src/Extractor/LinkedOpenVocabularies.php](./../scripts/src/Extractor/LinkedOpenVocabularies.php)

**Notes:**
* Used value of `dct:modified` for latest access; because the field only contains the date, the time is always set to `00:00:00`.

## Ontology Lookup Service (OLS)

https://www.ebi.ac.uk/ols4/

Related script: [scripts/src/Extractor/OntologyLookupService.php](./../scripts/src/Extractor/OntologyLookupService.php)

**Notes:**
* Warning: Currently ignoring all ontologies with no `fileLocation` field set in ontology configuration
* Field `ontology.uploaded` is used for latest access
