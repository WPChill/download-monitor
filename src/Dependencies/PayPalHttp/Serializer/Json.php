<?php

namespace Never5\DownloadMonitor\Dependencies\PayPalHttp\Serializer;

use Never5\DownloadMonitor\Dependencies\PayPalHttp\HttpRequest;
use Never5\DownloadMonitor\Dependencies\PayPalHttp\Serializer;

/**
 * Class Json
 * @package Never5\DownloadMonitor\Dependencies\PayPalHttp\Serializer
 *
 * Serializer for JSON content types.
 */
class Json implements Serializer
{

    public function contentType()
    {
        return "/^application\\/json/";
    }

    public function encode(HttpRequest $request)
    {
        $body = $request->body;
        if (is_string($body)) {
            return $body;
        }
        if (is_array($body)) {
            return json_encode($body);
        }
        throw new \Exception("Cannot serialize data. Unknown type");
    }

    public function decode($data)
    {
        return json_decode($data);
    }
}
