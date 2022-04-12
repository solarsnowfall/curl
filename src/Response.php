<?php

namespace Solar\Curl;

class Response
{
    /**
     * @var string
     */
    protected string $body = '';

    /**
     * @var array
     */
    protected array $headers = [];

    /**
     * @var string
     */
    protected string $rawResponse = '';

    /**
     * @param string $response
     */
    public function __construct(string $response)
    {
        $this->rawResponse = $response;

        preg_match_all('#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims', $response, $matches);

        $headerText = array_pop($matches[0]);

        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headerText));

        $this->body = substr($response, strlen($headerText));

        $versionAndStatus = array_shift($headers);

        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $versionAndStatus, $matches);

        $this->headers = [
            'Http-Version'  => $matches[1],
            'Status-Code'   => $matches[2],
            'Status'        => "{$matches[2]} {$matches[3]}"
        ];

        foreach ($headers as $header)
        {
            preg_match('#(.*?):\s(.*)#', $header, $matches);

            $this->headers[$matches[1]] = $matches[2];
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getBody();
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getRawResponse(): string
    {
        return $this->rawResponse;
    }
}