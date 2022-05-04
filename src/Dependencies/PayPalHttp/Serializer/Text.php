<?php

namespace WPChill\DownloadMonitor\Dependencies\PayPalHttp\Serializer;

use WPChill\DownloadMonitor\Dependencies\PayPalHttp\HttpRequest;
use WPChill\DownloadMonitor\Dependencies\PayPalHttp\Serializer;

/**
 * Class Text
 * @package WPChill\DownloadMonitor\Dependencies\PayPalHttp\Serializer
 *
 * Serializer for Text content types.
 */
class Text implements Serializer
{

    public function contentType()
    {
        return "/^text\\/.*/";
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
        return implode(" ", $body);
    }

    public function decode($data)
    {
        return $data;
    }
}
