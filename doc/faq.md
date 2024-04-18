## FAQ

In this section the most common questions are to be answered.

## Why no versioning information?

Version information of ontologies are not part of the index.
Instead, the latest version of the ontology is getting used.
The reason is that the effort is in no relation to the benefit.
For now we only aim to provide an index which is as complete as possible.

## Why providing the index as CSV file?

**Low entry barrier.**
CSV files are universally readable and easy to work with.
People need almost no prior knowledge to understand the file structure.
Another advantage is the low memory footprint when parsing a CSV file, because you can read it line by line.
If it were in a format, where you always have to read the whole file before processing it (such as JSON), you would need more memory the bigger the index file gets.
