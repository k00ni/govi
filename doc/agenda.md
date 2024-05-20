# Agenda

The following list is more meant for internal usage, but if you wanna help out with one of the topics, feel free to open an issue or create a pull request.

## Version 0.1 (proof of concept)

**Objective:** Build a proof of concept to see if the idea is doable and brings any significant value.

* [x] Simple scripts to download, parse and read ontologies of interest and generate a CSV file in the end
* include ontology entries of the following services:
  * [x] DBpedia Archivo: https://archivo.dbpedia.org/list
  * [x] Linked Open Vocabularies: https://lov.linkeddata.es/dataset/lov/
  * [x] Ontology Lookup Service: https://www.ebi.ac.uk/ols4/api/ontologies
  * [x] BioPortal: https://bioportal.bioontology.org/
* [x] provide a way to contribute meta data using Github Pull Requests (use CSV file as well) - see [manually-maintained-metadata-about-ontologies](./manually-maintained-metadata-about-ontologies.csv)
* [x] use manually maintained CSV file to fill in blanks in index.db (e.g. missing license info in OWL file)
* the following meta data are of interest:
  * [x] valid URL to latest version of RDF/XML-, Turtle-, NTriples- or N3-file
  * [x] name
  * [x] date time of last check
  * [x] data source info
  * [x] short description/summary
  * [x] license
  * [x] authors + contributors
  * [x] project page / homepage
  * [x] data source url

## Version 0.2

* [ ] implement basic logging
* [ ] add doap file
* add further services:
  * [x] ~~https://obofoundry.org/~~ - ontologies are also in BioPortal
  * [x] ~~http://www.oegov.us/~~ - manually added, because of HTML structure and very old entries
  * [ ] http://ontologydesignpatterns.org/wiki/Main\_Page
  * [ ] https://github.com/linkeddata/ontology-archiver
  * [ ] crawl Github repositories tagged with "ontology" etc.
* [ ] harmonize datetime information for latest access (all UTC?)
* [ ] add basic schema/ontology describing the fields in index.csv
* [ ] add a way to manually provide entries via Github
* [ ] Ping service: on update call a list of URLs to let them know that there was a change
* [ ] generate statistics for each service read to build index.csv (contains number of entries etc.)
* [ ] check prior versions of an ontology to avoid adding the same ontology just with different versions
  * [ ] http vs https
  * [ ] / vs # at the end
* [ ] mark entries if they contain SKOS entries
