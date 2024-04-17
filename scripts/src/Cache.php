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

    private string $filesFolder = __DIR__.'/../var/downloaded_rdf_files/';

    private function getCacheInstance(string $namespace): AbstractAdapter
    {
        if (false === isset($this->caches[$namespace])) {
            $this->caches[$namespace] = new FilesystemAdapter($namespace, 0, __DIR__.'/../var');
        }

        return $this->caches[$namespace];
    }

    private function createSimplifiedFilename(string $fileUrl): string
    {
        return (string) preg_replace('/[^a-z0-9\-_]/ism', '_', $fileUrl);
    }

    /**
     * @return non-empty-string
     *
     * @throws \Exception
     */
    public function getCachedFilePathForFileUrl(string $fileUrl): string
    {
        $fileRes = $this->getLocalFileResourceForFileUrl($fileUrl);

        if (is_resource($fileRes)) {
            // generate simplified filename for local storage
            /** @var non-empty-string */
            $result = $this->filesFolder.$this->createSimplifiedFilename($fileUrl);
            return $result;
        } else {
            throw new Exception('Got no file resource for '.$fileUrl);
        }
    }

    /**
     * @return resource|false Return value of fopen(..., 'r')
     *
     * @throws \Exception in case of an CURL error
     */
    public function getLocalFileResourceForFileUrl(string $fileUrl)
    {
        $filename = $this->createSimplifiedFilename($fileUrl);
        $filepath = $this->filesFolder.$filename;

        echo PHP_EOL.$fileUrl.' >> '.$filename;

        if (false === file_exists($filepath)) {
            echo ' ==> DOWNLOAD REQUIRED';
            $curl = new Curl();

            // timeout until conntected
            $curl->setConnectTimeout(5);
            // time of curl to execute (seconds)
            $curl->setTimeout(3000);

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
        $key = $this->createSimplifiedFilename($url);

        // ask cache for entry
        // if there isn't one, run HTTP request and return response content
        return $cache->get($key, function () use ($url): string {
            $curl = new Curl();

            // timeout until conntected
            $curl->setConnectTimeout(5);
            // time of curl to execute
            $curl->setTimeout(3000);

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
