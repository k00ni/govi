<?php

declare(strict_types=1);

namespace App;

use Curl\Curl;
use Exception;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class Cache
{
    /**
     * @var array<string,\Symfony\Component\Cache\Adapter\AbstractAdapter>
     */
    private array $caches = [];

    private function getCacheInstance(string $namespace): AbstractAdapter
    {
        if (false === isset($this->caches[$namespace])) {
            $this->caches[$namespace] = new FilesystemAdapter($namespace, 0, __DIR__.'/../var');
        }

        return $this->caches[$namespace];
    }

    /**
     * @return resource|false Return value of fopen(..., 'r')
     *
     * @throws \Exception in case of an CURL error
     */
    public function getLocalFileResourceForFileUrl(string $fileUrl)
    {
        $filesFolder = __DIR__.'/../var/downloaded_rdf_files/';

        // generate simplified filename for local storage
        $filename = preg_replace('/[^a-z0-9\-_]/ism', '_', $fileUrl);

        $filepath = $filesFolder.$filename;

        echo PHP_EOL.$fileUrl.' >> '.$filename;

        if (false === file_exists($filepath)) {
            echo ' ==> DOWNLOAD REQUIRED';
            $curl = new Curl();

            // timeout until conntected
            $curl->setConnectTimeout(5);
            // time of curl to execute (seconds)
            $curl->setTimeout(300);

            $curl->setMaximumRedirects(10);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true); // follow redirects

            // ignore broken/invalid SSL certificats
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);

            // Enable all supported encoding types
            $curl->setOpt(CURLOPT_ENCODING, '');
            $foundErrors = false === $curl->download($fileUrl, $filepath);

            if ($foundErrors) {
                throw new Exception('Curl error for '.$fileUrl.' >>> '.$curl->getErrorMessage());
            }
        } else {
            echo ' ==> CACHE used';
        }

        return fopen($filepath, 'r');
    }

    /**
     * Cache responses for a while to reduce server load.
     *
     * @throws \Exception if curl found an error
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function sendCachedRequest(string $url, string $namespace): string
    {
        $cache = $this->getCacheInstance($namespace);
        $key = (string) preg_replace('/[\W]/', '_', $url);

        // ask cache for entry
        // if there isn't one, run HTTP request and return response content
        return $cache->get($key, function () use ($url): string {
            $curl = new Curl();

            // timeout until conntected
            $curl->setConnectTimeout(5);
            // time of curl to execute
            $curl->setTimeout(300);

            $curl->setMaximumRedirects(10);
            $curl->setOpt(CURLOPT_FOLLOWLOCATION, true); // follow redirects

            // ignore broken/invalid SSL certificats
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST, false);

            $curl->get($url);

            if ($curl->isError()) {
                throw new Exception('CURL error: '.$curl->getErrorMessage());
            }

            return $curl->rawResponse;
        });
    }
}
