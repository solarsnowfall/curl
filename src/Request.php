<?php

namespace Solar\Curl;

class Request
{
    const REQUEST_TYPE_DELETE = 'DELETE';
    const REQUEST_TYPE_HEAD =   'HEAD';
    const REQUEST_TYPE_GET =    'GET';
    const REQUEST_TYPE_POST =   'POST';
    const REQUEST_TYPE_PUT =    'PUT';

    /**
     * @var int
     */
    protected int $errorCode = 0;

    /**
     * @var string
     */
    protected string $errorMessage = '';

    /**
     * @var array
     */
    protected array $headers = [];

    /**
     * @var array
     */
    protected array $options;

    /**
     * @var resource $resource
     */
    protected $resource = null;

    /**
     * @var array
     */
    protected array $requestOptions = [
        CURLOPT_HEADER          => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_USERAGENT       => '',
        CURLOPT_REFERER         => ''
    ];

    /**
     * @var bool
     */
    protected bool $throwsException = true;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        $this->requestOptions[CURLOPT_USERAGENT] = $this->resolveUserAgent();

        $this->requestOptions[CURLOPT_REFERER] = $this->resolveReferer();
    }

    /**
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array
     */
    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }

    /**
     * @return bool
     */
    public function getThrowsException(): bool
    {
        return $this->throwsException;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param bool $followLocation
     * @return $this
     */
    public function setFollowLocation(bool $followLocation): self
    {
        $this->requestOptions[CURLOPT_FOLLOWLOCATION] = $followLocation;

        return $this;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param bool $throwsException
     * @return $this
     */
    public function setThrowsException(bool $throwsException): self
    {
        $this->throwsException = $throwsException;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param string $url
     * @param array $parameters
     * @return bool|Response
     * @throws \Exception
     */
    public function delete(string $url, array $parameters)
    {
        return $this->execute(static::REQUEST_TYPE_DELETE, $url, $parameters);
    }

    /**
     * @param string $key
     * @return $this
     */
    public function deleteHeader(string $key): self
    {
        unset($this->headers[$key]);

        return $this;
    }

    /**
     * @param string $method
     * @param string $url
     * @param array|null $parameters
     * @return Response|bool
     * @throws \Exception
     */
    public function execute(string $method, string $url, ?array $parameters = null)
    {
        $method = strtoupper($method);

        if ($method === 'GET' && $parameters !== null)
        {
            $url .= '?' . http_build_query($parameters);

            $parameters = null;
        }

        $this->resource = curl_init($url);

        $this->applyRequestOptions($method, $parameters);

        $this->applyHeaders();

        $response = curl_exec($this->resource);

        $this->errorCode = curl_errno($this->resource);

        $this->errorMessage = curl_error($this->resource);

        if ($response === false)
        {
            if ($this->throwsException)
                throw new \Exception($this->errorMessage, $this->errorCode);

            return false;
        }

        return new Response($response);
    }

    /**
     * @param string $url
     * @param array|null $parameters
     * @return bool|Response
     * @throws \Exception
     */
    public function get(string $url, array $parameters = null)
    {
        return $this->execute(static::REQUEST_TYPE_GET, $url, $parameters);
    }

    /**
     * @param string $url
     * @param array|null $parameters
     * @return bool|Response
     * @throws \Exception
     */
    public function head(string $url, array $parameters = null)
    {
        return $this->execute(static::REQUEST_TYPE_HEAD, $url, $parameters);
    }

    /**
     * @param string $url
     * @param array|null $parameters
     * @return bool|Response
     * @throws \Exception
     */
    public function post(string $url, array $parameters = null)
    {
        return $this->execute(static::REQUEST_TYPE_POST, $url, $parameters);
    }

    /**
     * @param string $url
     * @param array|null $parameters
     * @return bool|Response
     * @throws \Exception
     */
    public function put(string $url, array $parameters = null)
    {
        return $this->execute(static::REQUEST_TYPE_PUT, $url, $parameters);
    }

    /**
     * @return string
     */
    public function resolveReferer(): string
    {
        return $_SERVER['HTTP_REFERER'] ?? '';
    }

    /**
     * @return string
     */
    public function resolveUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? __CLASS__ . ' Client 1.0';
    }

    /**
     * @return $this
     */
    protected function applyHeaders(): self
    {
        $headers = [];

        foreach ($this->headers as $key => $value)
            $headers[] = "$key: $value";

        $this->applyOptions([CURLOPT_HTTPHEADER => $headers]);

        return $this;
    }

    /**
     * @param string $method
     * @param ?array $parameters
     * @return Request
     */
    protected function applyRequestOptions(string $method, array $parameters = null): self
    {
        $options = array_filter(array_replace($this->requestOptions, $this->options), function ($value) {
            return $value !== null && $value !== '';
        });

        if ($parameters !== null)
            $options[CURLOPT_POSTFIELDS] = $this->encodeParameters($parameters);

        switch ($method)
        {
            case self::REQUEST_TYPE_GET:

                $options[CURLOPT_HTTPGET] = true;
                break;

            case self::REQUEST_TYPE_HEAD:

                $options[CURLOPT_NOBODY] = true;
                break;

            case self::REQUEST_TYPE_POST:

                $options[CURLOPT_POST] = true;
                break;

            default:

                $options[CURLOPT_CUSTOMREQUEST] = $method;
        }

        return $this->applyOptions($options);
    }

    /**
     * @param array $parameters
     * @return string
     */
    protected function encodeParameters(array $parameters): string
    {
        return http_build_query($parameters);
    }

    /**
     * @param array $options
     * @return $this
     */
    private function applyOptions(array $options): self
    {
        curl_setopt_array($this->resource, $options);

        return $this;
    }
}