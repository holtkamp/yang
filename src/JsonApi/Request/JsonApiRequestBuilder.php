<?php
declare(strict_types=1);

namespace WoohooLabs\Yang\JsonApi\Request;

use Psr\Http\Message\RequestInterface;
use WoohooLabs\Yang\JsonApi\Serializer\JsonSerializer;
use WoohooLabs\Yang\JsonApi\Serializer\SerializerInterface;

class JsonApiRequestBuilder
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $protocolVersion;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $queryString;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var mixed
     */
    private $body;

    public function __construct(RequestInterface $request, ?SerializerInterface $serializer = null)
    {
        $this->request = $request;
        $this->serializer = $serializer ?? new JsonSerializer();
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->method = "GET";
        $this->protocolVersion = "";
        $this->scheme = "http";
        $this->host = "";
        $this->path = "";
        $this->queryString = [];
        $this->headers = [];
    }

    public function fetch(): JsonApiRequestBuilder
    {
        return $this->setMethod("GET");
    }

    public function create(): JsonApiRequestBuilder
    {
        return $this->setMethod("POST");
    }

    public function update(): JsonApiRequestBuilder
    {
        return $this->setMethod("PATCH");
    }

    public function delete(): JsonApiRequestBuilder
    {
        return $this->setMethod("DELETE");
    }

    public function setMethod(string $method): JsonApiRequestBuilder
    {
        $this->method = $method;

        return $this;
    }

    public function setProtocolVersion(string $version): JsonApiRequestBuilder
    {
        $this->protocolVersion = $version;

        return $this;
    }

    public function http(): JsonApiRequestBuilder
    {
        return $this->setUriScheme("http");
    }

    public function https(): JsonApiRequestBuilder
    {
        return $this->setUriScheme("https");
    }

    public function setUri(string $uri): JsonApiRequestBuilder
    {
        $parsedUrl = parse_url($uri);

        if ($parsedUrl === false) {
            return $this;
        }

        if (empty($parsedUrl["scheme"]) === false) {
            $this->scheme = $parsedUrl["scheme"];
        }

        if (empty($parsedUrl["port"]) === false) {
            $this->port = (int) $parsedUrl["port"];
        }

        if (empty($parsedUrl["host"]) === false) {
            $this->host = $parsedUrl["host"];
        }

        if (empty($parsedUrl["path"]) === false) {
            $this->path = $parsedUrl["path"];
        }

        if (empty($parsedUrl["query"]) === false) {
            parse_str($parsedUrl["query"], $this->queryString);
        }

        return $this;
    }

    public function setUriScheme(string $scheme): JsonApiRequestBuilder
    {
        $this->scheme = $scheme;

        return $this;
    }

    public function setUriHost(string $host): JsonApiRequestBuilder
    {
        $this->host = $host;

        return $this;
    }

    public function setUriPort(int $port): JsonApiRequestBuilder
    {
        $this->port = $port;

        return $this;
    }

    public function setUriPath(string $path): JsonApiRequestBuilder
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param string|array $value
     */
    public function setUriQueryParam(string $name, $value): JsonApiRequestBuilder
    {
        $this->queryString[$name] = $value;

        return $this;
    }

    /**
     * @param string|string[] $value
     */
    public function setHeader(string $name, $value): JsonApiRequestBuilder
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function setJsonApiFields(array $fields): JsonApiRequestBuilder
    {
        $this->setArrayQueryParam("fields", $fields);

        return $this;
    }

    public function setJsonApiSort(array $sort): JsonApiRequestBuilder
    {
        $this->setListQueryParam("sort", $sort);

        return $this;
    }

    public function setJsonApiPage(array $paginate): JsonApiRequestBuilder
    {
        $this->setArrayQueryParam("page", $paginate);

        return $this;
    }

    public function setJsonApiFilter(array $filter): JsonApiRequestBuilder
    {
        $this->setArrayQueryParam("filter", $filter);

        return $this;
    }

    /**
     * @param array|string $includes
     */
    public function setJsonApiIncludes($includes): JsonApiRequestBuilder
    {
        $this->setListQueryParam("include", $includes);

        return $this;
    }

    /**
     * @param ResourceObject|array|string $body
     */
    public function setJsonApiBody($body): JsonApiRequestBuilder
    {
        if ($body instanceof ResourceObject) {
            $this->body = $body->toArray();
        } else {
            $this->body = $body;
        }

        return $this;
    }

    public function getRequest(): RequestInterface
    {
        $request = $this->request->withMethod($this->method);
        $uri = $request
            ->getUri()
            ->withScheme($this->scheme)
            ->withHost($this->host)
            ->withPort($this->port)
            ->withPath($this->path)
            ->withQuery($this->getQueryString());

        $request = $request
            ->withUri($uri)
            ->withProtocolVersion($this->protocolVersion)
            ->withHeader("Accept", "application/vnd.api+json")
            ->withHeader("Content-Type", "application/vnd.api+json");

        foreach ($this->headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->serializer->serialize($request, $this->body);
    }

    private function getQueryString(): string
    {
        return http_build_query($this->queryString);
    }

    private function setArrayQueryParam(string $name, array $queryParam): void
    {
        foreach ($queryParam as $key => $value) {
            if (is_array($value)) {
                $this->queryString[$name][$key] = implode(",", $value);
            } else {
                $this->queryString[$name][$key] = $value;
            }
        }
    }

    /**
     * @param array|string $queryParam
     */
    private function setListQueryParam(string $name, $queryParam): void
    {
        if (is_array($queryParam)) {
            $this->queryString[$name] = implode(",", $queryParam);
        } else {
            $this->queryString[$name] = $queryParam;
        }
    }
}
