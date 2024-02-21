# Global Ontology and Vocabulary Index (govi)

The Global Ontology and Vocabulary Index (govi) is meant to be a Community-driven project to maintain an index of all available ontologies and vocabularies.
The index is basically a list of links (URLs) to ontologies and vocabularies, plus a few meta data.

## Why?

There is **no** search engine or even a basic list of all ontologies/vocabularies available right now (*LOD graph and Internet in general*).
To answer the question **What ontologies/vocabularies exist and where can I find them?** people have to manually check ontology portals (such as BioPortal), online catalogs and similar services, each covering a different set of domains but only link to a subset of ontologies/vocabularies.
This project aims to answer the question by providing a simplified list of ontologies/vocabularies.

**Important note:** Websites like BioPortal and DBpedia Archivo play an important role for the community, because they allow people to browse and search ontologies as well as provide additional services such as versioning, archives etc.
This project is not meant to replace them, on the contrary, we endorse services like [DBpedia Archivo](https://archivo.dbpedia.org/), because they tackle important challanges, such as link rot and inconsistent versioning.
Portals like BioPortal are important too, but they often provide ontologies as data dump or SPARQL endpoint instead of an URL which leads to the source code (e.g. OWL, N3 or TTL).
As long as the portal is online, everything is fine but as soon as it goes offline, all ontologies are gone if there is no copy somewhere else.

```

            User: - Give me a list of all ontologies!
                  - ...
                                    ||
                                    ||
                                    \/
  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++|
  |                                                                             |
  |              Global Ontologies and Vocabulary Index (GOVI)                  |
  |                                                                             |
  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++|
        ||                      ||                      ||
        ||                      ||                      ||
        ||                      ||                      ||
        \/                      \/                      \/
  Ontology Portals      Ontology Archives           Ontology Catalogs       ...
  (e.g. BioPortal)      (e.g. DBpedia Archivo)      (e.g. LOV)

```

## Versions

### Version 0.1 (proof-of-concept)

* [x] Simple scripts to download, parse and read ontologies of interest and generate a CSV file in the end
  * [x] include DBpedia Archivo: https://archivo.dbpedia.org/list
  * [ ] include BioPortal: https://bioportal.bioontology.org/
  * [ ] include LOV (Linked Open Vocabularies): https://lov.linkeddata.es/dataset/lov/vocabs
  * [ ] ...
* the following meta data are of interest:
  * [ ] URL to latest version of OWL-, TTL- and/or NT file
  * [x] name / title
  * [x] date time of last check

### Version 0.2

* [ ] add basic schema/ontology describing the fields in index.csv
* [ ] check if owl:Ontology relation is found in source file (to make sure its an OWL ontology)
* the following meta data are of interest:
  * [ ] info whether its a direct link or ontology is stored in an ontology portal
  * [ ] (short) description
  * [ ] link to license or license text
  * [ ] link or name to authors or responsible group
  * [ ] covered domain
  * [ ] used keywords
* [ ] crawl Github repositories tagged with "ontology" etc.

## What this project is not

### No versioning information

[...]

## Contributions and Local development

[...]
