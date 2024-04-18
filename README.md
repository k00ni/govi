# Global Ontology and Vocabulary Index (GOVI)

The Global Ontology and Vocabulary Index (GOVI) is meant to be a Community-driven project to maintain an index of all available RDF ontologies and vocabularies.
The index is basically a big [CSV](https://en.wikipedia.org/wiki/Comma-separated_values) file, containing a list of URLs to ontologies and vocabularies, plus a few meta data (such as authors, license information and latest modification date).

The index file [**index.csv**](./index.csv) contains over over **3000+** ontologies and vocabularies.
You can use the GOVI-browser https://govi-browser.inspirito.de/ to easily browse it.

---

### Overview

* [Summary of the content of the index](#summary-of-the-content-of-the-index)
* [Why?](#why)
* Documentation:
  * [Agenda](./doc/agenda.md)
  * [FAQ](./doc/faq.md)
  * [Information on each data source](./doc/information-on-each-data-source.md)

---

### Summary of the content of the index

An RDF ontology (including vocabularies) is part of the index if it meets the following criteria:
* non-empty, valid ontology title
* non-empty, valid ontology IRI
* at least one valid URL to a RDF file is available (JSON-LD, N3, Ntriples, RDF/XML or Turtle)
* at least one instance/subclasses of one of the following classes was found: `owl:Ontology`, `owl:Class`, `rdf:Property`, `rdfs:Class`, `skos:Concept`

If an entry is part of multiple sources (e.g. LOV and DBpedia Archivo), the one which appears first is taken.

## Why?

Where do you go if you wanna answer the following question:

> **What ontologies/vocabularies exist and where can I find them?**

There is currently **no** search engine or even a basic list of all ontologies/vocabularies (of the LOD graph or the Internet in general).
There may never be such a list because ontologies/vocabularies come and go (e.g. [link rot](https://en.wikipedia.org/wiki/Link_rot)).
But the question remains, and to answer it one has to manually check ontology portals (like BioPortal), archive services (like DBpedia Archivo) and similar services.
Each service covers only a subset of ontologies/vocabularies.
This project aims to answer this question by providing a simplified list of RDF ontologies/vocabularies.
The list was created by asking available ontology portals, archive services, etc. about their ontologies and vocabularies.
**We're standingon the shoulders of giants here** because the teams behind these services do the hard work.
Also, people can also provide metadata via this repository, although we recommend using a suitable service (like LOV) instead.
Over time, this place could grow to reference (almost) all ontologies/vocabularies.

```

        People: What ontologies/vocabularies exist and where can I find them?
                                      ||
                                      ||
                                      \/
  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++|
  |                                                                               |
  |              Global Ontologies and Vocabulary Index (GOVI)                    |
  |                                                                               |
  ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++|
        ||                      ||                                ||
        ||                      ||                                ||
        ||                      ||                                ||
        \/                      \/                                \/
  Ontology Portals      Ontology Archives           Ontology Catalogs       ...
  (e.g. BioPortal)      (e.g. DBpedia Archivo)      (e.g. Linked Open Vocabularies)

```

### Whats wrong with Ontology portals, online catalogs, ...?

**Nothing**. Services such as BioPortal and DBpedia Archivo play an important role for the community because they provide user-friendly access to browse ontologies and provide additional services such as versioning, archives, etc.
This project is **not** meant to replace them, on the contrary, we support services like [DBpedia Archivo](https://archivo.dbpedia.org/) because they address important challenges like link rot and inconsistent versioning.
Portals such as BioPortal are also important because of their user-friendly approach (e.g. browsing class hierarchies, searching, etc.).
But portals often provide ontologies as a data dump or SPARQL endpoint instead of a dereferenceable URL (for accessing the RDF/OWL code).
As long as the portal is online, everything is fine, but as soon as it goes offline, all ontologies/vocabularies are gone unless there is a copy somewhere else.

## Contributions

Contributions are welcome.
Please use pull requests on Github.

## Documentation

Further information can be found in [doc](./doc/) folder.

## License

Copyright (C) 2024 [Konrad Abicht](https://inspirito.de) and contributors.

The code and development material (e.g. documentation) of GOVI is licenced under the terms of the [GNU GPL v2](./LICENSE).

The content of the [index.csv](./index.csv) and [manually-maintained-metadata-about-ontologies.csv](./manually-maintained-metadata-about-ontologies.csv) is licenced under the terms of the [CC0 1.0 DEED (Public Domain)](https://creativecommons.org/publicdomain/zero/1.0/), because it only contains content which was already published on the Internet.
The rights of the ontology/vocabulary authors shall remain reserved.
