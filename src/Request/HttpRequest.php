<?php
namespace src\Request;

use src\Request\Query;

class HttpRequest
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    public $requestBag = [];
    public $queryBag = [];
    private $method;
    private $host;
    private $protocol;

    public function __construct(Query $query, string $host, string $protocol)
    {
        $this->requestBag = &$_GET;
        $this->queryBag = &$_POST;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->host = $host;
        $this->protocol = $protocol;
    }
}