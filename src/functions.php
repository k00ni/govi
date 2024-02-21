<?php

declare(strict_types=1);

/**
 * It seems that empty() is not enough to check, if something is really empty.
 * This function takes care of the edge cases.
 *
 * @see https://stackoverflow.com/questions/718986/checking-if-the-string-is-empty
 */
function isEmpty(string|null $input): bool
{
    if (null === $input) {
        return true;
    } else { // its a string
        $input = trim($input);
        $input = (string) preg_replace('/\s/', '', $input);

        return 0 == strlen($input);
    }
}

/**
 * Merges a given temporay index into our index.csv file.
 *
 * @param array<string,string|null> $temporaryIndex
 *
 * @throws Exception if file($pathToIndexCSV) failed.
 * @throws Exception if index.csv does not exist.
 * @throws Exception if index.csv could not be open.
 */
function mergeEntriesIntoIndexCSV(string $sourceUrl, array $temporaryIndex): void
{
    // path to index.csv
    $pathToIndexCSV = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'index.csv';

    if (file_exists($pathToIndexCSV)) {
        $fileArr = file($pathToIndexCSV);

        if (is_array($fileArr)) {
            $indexCSV = array_map('str_getcsv', $fileArr);

            // save head
            $head = $indexCSV[0];
            unset($indexCSV[0]);

            // first remove all entries in index.csv which are related to given temporary index
            foreach ($indexCSV as $key => $entry) {
                if ($entry[6] == $sourceUrl) {
                    unset($indexCSV[$key]);
                }
            }

            // add all entries from temporary index
            /** @var array<int|string,array<int|string,string|null>> */
            $indexCSV = array_merge($indexCSV, $temporaryIndex);

            // sort entries by name
            usort($indexCSV, function ($a, $b) {
                $aTitle = $a[0] ?? '';
                $bTitle = $b[0] ?? '';

                return $aTitle < $bTitle ? -1 : 1;
            });

            // store file
            $fp = fopen($pathToIndexCSV, 'w');
            if (is_resource($fp)) {
                // add head
                fputcsv($fp, $head);

                foreach ($indexCSV as $fields) {
                    fputcsv($fp, $fields);
                }
                fclose($fp);
            } else {
                throw new Exception('Could not open '.$pathToIndexCSV.' to write');
            }
        } else {
            throw new Exception('file($pathToIndexCSV) failed.');
        }

    } else {
        throw new Exception('File '. $pathToIndexCSV .' does not exist!');
    }
}
