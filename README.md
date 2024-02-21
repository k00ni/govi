# Global Ontology and Vocabulary Index (govi)

The Global Ontology and Vocabulary Index (govi) is a Community-driven project to generate an index of all available ontologies and vocabularies.
The index is basically a list of links (URLs, URIs) to ontologies and vocabularies, plus a few meta data.

## Why?

There is no central endpoint or search engine for ontologies out there, which covers (almost) all ontologies out there.
Instead there are many ontology portals with different UIs, APIs and different coverage of ontologies and domains.
If you want to get an overview over (almost) all available ontologies you have to manually check all these portals.

**Important note:** Ontology portals like BioPortal or DBpedia Archivo play an important role, because they provide easier access to ontology information.
This project is not meant to replace them! Instead, the objective is to provide an neutral and as complete as possible map of existing ontologies.

We endorse services like [DBpedia Archivo](https://archivo.dbpedia.org/), because they provide basic yet powerful way to serve ontologies to the world. Portals can be useful and serve an important purpose, but if they close, all their data will be gone and not accessible anymore.

## Versions

### Version 0.1 (proof-of-concept)

* [ ] Provide a Github-based, text-oriented approach to collect (and configure) ontology portals to crawl
* [ ] Simple scripts to download, parse and read ontologies of interest and generate a CSV file in the end
  * the following meta data are of interest:
    * [x] URL to latest version of OWL-, TTL- and/or NT file
    * [x] name / title
    * [ ] (short) description
    * [ ] info whether its a direct link or ontology is stored in an ontology portal
    * [ ] link to license or license text
    * [ ] link or name to authors or responsible group
    * [ ] covered domain
    * [ ] used keywords
    * [ ] date time of last check

### Version 0.2

* [ ] crawl Github repositories tagged with "ontology" etc.

## What this project is not

### No versioning information

[...]

## Contributions and Local development

[...]
