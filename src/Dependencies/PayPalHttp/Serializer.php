<?php

namespace Never5\DownloadMonitor\Dependencies\PayPalHttp;

/**
 * Interface Serializer
 * @package Never5\DownloadMonitor\Dependencies\PayPalHttp
 *
 * Used to implement different serializers for different content types
 */
interface Serializer
{
    /**
     * @return string Regex that matches the content type it supports.
     */
    public function contentType();

    /**
     * @param HttpRequest $request
     * @return string representation of your data after being serialized.
     */
    public function encode(HttpRequest $request);

    /**
     * @param $body
     * @return mixed object/string representing the de-serialized response body.
     */
    public function decode($body);
}
