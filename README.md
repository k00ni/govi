# Global Ontology and Vocabulary Index (GOVI)

:construction: Still work in progress 🚧

The Global Ontology and Vocabulary Index (GOVI) is meant to be a Community-driven project to maintain an index of all available RDF ontologies and vocabularies.
The index is basically a big [CSV](https://en.wikipedia.org/wiki/Comma-separated_values) file, containing a list of URLs to ontologies and vocabularies, plus a few meta data.

You can find the index file here: [**index.csv**](./index.csv)

### Requirements and rules for an ontology-entry in the index

An RDF ontology/vocabulary is part of the index if it meets the following requirements:
* non-empty, valid title
* non-empty, valid URI
* at least one valid URL to a RDF file

If an entry is part of multiple sources (e.g. LOV and DBpedia Archivo), the one which appears first is taken.

## Why?

Where do you go if you wanna answer the following question:

> **What ontologies/vocabularies exist and where can I find them?**

There is **no** search engine or even a basic list of all ontologies/vocabularies available right now (of the LOD graph or Internet in general).
There might never be such a list, because ontologies/vocabularies come and go (e.g. [link rot](https://en.wikipedia.org/wiki/Link_rot)).
But the question remains, so to answer it people have to manually check ontology portals (such as BioPortal), archive services (such as DBpedia Archivo) and similar services.
Each service is covering only a subset of ontologies/vocabularies.
This project aims to answer the question by providing a simplified list of RDF ontologies/vocabularies.
The list is generated by asking available ontology portals, archive services etc. about their ontologies and vocabularies.
**We are standing on the shoulders of giants here**, because the teams behind these services are doing the hard work.
Besides, people can also provide meta data via this repository, although we recommend using an appropriate service instead (such as LOV).
Over time this place might grow and reference (almost) all ontologies/vocabularies.

```

      People: What ontologies/vocabularies exist and where can I find them?
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

## Whats wrong with Ontology portals, online catalogs, ...?

**Nothing**.

Services such as BioPortal and DBpedia Archivo play an important role for the community, because they provide user-friendly access for people to browse and search ontologies as well as provide additional services such as versioning, archives etc.
This project is **not** meant to replace them, on the contrary, we endorse services like [DBpedia Archivo](https://archivo.dbpedia.org/), because they tackle important challanges, such as link rot and inconsistent versioning.
Portals like BioPortal are important too because of their user friendly approach (e.g. browse class hierarchies, search, etc.).
But portals often provide ontologies as data dump or SPARQL endpoint instead of a dereferenceable URL (to access the RDF/OWL code).
As long as the portal is online everything is fine but as soon as it goes offline, all ontologies/vocabularies are gone if there is no copy somewhere else.

## Information on each source

### BioPortal

https://data.bioontology.org/documentation

Related script: [scripts/bin/read-bioportal](scripts/bin/read-bioportal)

**Notes:**
* scripts tries frontend view of ontology first to get a RDF file, if no luck, it tries `ontology.links.download` (such as https://data.bioontology.org/ontologies/INFRARISK/download), because they come in non-RDF formats (e.g. obo)
* further work is required to get more out of BioPortal (e.g. uncompress archives containing RDF data and check them)

### DBpedia Archivo

https://archivo.dbpedia.org/list

Related script: [scripts/bin/read-dbpedia-archivo](scripts/bin/read-dbpedia-archivo)

**Notes:**
* Used value of "Latest Timestamp" for latest access, "2020.06.10-175249" is interpreted as "2020-06-10 00:00:00"

### Linked Open Vocabularies (LOV)

https://lov.linkeddata.es/dataset/lov/

Related script: [scripts/bin/read-linked-open-vocabularies](scripts/bin/read-linked-open-vocabularies)

**Notes:**
* Used value of `dct:modified` for latest access; because the field only contains the date, the time is always set to `00:00:00`.

### Ontology Lookup Service (OLS)

https://www.ebi.ac.uk/ols4/

Related script: [scripts/bin/read-linked-open-vocabularies](scripts/bin/read-ontology-lookup-service)

**Notes:**
* Warning: Currently ignoring all ontologies with no `fileLocation` field set in ontology configuration
* ontology.uploaded is used for latest access

## FAQ

In this section the most common question are to be answered.

### No versioning information

Version information of ontologies are not part of the index.
Instead, the latest version of the ontology is getting used.
The reason is that the effort is in no relation to the benefit.
For now we only aim to provide an index which is as complete as possible.

### Why providing the index as CSV file?

**Low entry barrier.**
CSV files are universally readable and easy to work with.
People need almost no prior knowledge to understand the file structure.
Another advantage is the low memory footprint when parsing a CSV file, because you can read it line by line.
If it were in a format, where you always have to read the whole file before processing it (such as JSON), you would need more memory the bigger the index file gets.

## Contributions and Local development

[...]

## Versions

The following list is more meant for internal usage.
But if you wanna help out with one of the topics, feel free to open an issue or create a pull request.

### Version 0.1 (proof-of-concept)

**Objective:** Build a basic prototype to see if the idea is doable and brings any significant value

* [x] Simple scripts to download, parse and read ontologies of interest and generate a CSV file in the end
* include ontology entries of the following services:
  * [x] DBpedia Archivo: https://archivo.dbpedia.org/list
  * [x] Linked Open Vocabularies: https://lov.linkeddata.es/dataset/lov/
  * [x] Ontology Lookup Service: https://www.ebi.ac.uk/ols4/api/ontologies
  * [x] BioPortal: https://bioportal.bioontology.org/
* [x] provide a way to contribute meta data using Github Pull Requests (use CSV file as well) - see [manually-maintained-metadata-about-ontologies](./manually-maintained-metadata-about-ontologies.csv)
* the following meta data are of interest:
  * [x] valid URL to latest version of RDF/XML-, Turtle-, NTriples- or N3-file
  * [x] name
  * [x] date time of last check
  * [x] data source info

### Version 0.2

* [ ] implement basic logging
* [ ] add doap file
* add further services:
  * [ ] https://obofoundry.org/
  * [ ] http://www.oegov.us/
  * [ ] https://www.ebi.ac.uk/ols4
  * [ ] http://ontologydesignpatterns.org/wiki/Main\_Page
  * [ ] https://obofoundry.org/
  * [ ] https://github.com/linkeddata/ontology-archiver
* [ ] harmonize datetime information for latest access (all UTC?)
* [ ] add basic schema/ontology describing the fields in index.csv
* [ ] check if owl:Ontology relation is found in source file (to make sure its an OWL ontology)
* [ ] crawl Github repositories tagged with "ontology" etc.
* [ ] add a way to manually provide entries via Github
* [ ] Ping service: on update call a list of URLs to let them know that there was a change
* [ ] generate statistics for each service read to build index.csv (contains number of entries etc.)

## License

Copyright (C) 2024 [Konrad Abicht](https://inspirito.de) and contributors.

The code and development material (e.g. documentation) of GOVI is licenced under the terms of the [GNU GPL v2](./LICENSE).

The content of the [index.csv](./index.csv) and [manually-maintained-metadata-about-ontologies.csv](./manually-maintained-metadata-about-ontologies.csv) is licenced under the terms of the [CC0 1.0 DEED (Public Domain)](https://creativecommons.org/publicdomain/zero/1.0/), because it only contains content which was already published on the Internet.
The rights of the ontology/vocabulary authors shall remain reserved.
