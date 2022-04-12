<?php

namespace Solar\Curl;

class JsonRequest extends Request
{
    /**
     * @var string[]
     */
    protected array $headers = [
        'Content-Type' => 'application/json'
    ];

    /**
     * @param array $parameters
     * @return string
     */
    protected function encodeParameters(array $parameters): string
    {
        return json_encode($parameters);
    }
}