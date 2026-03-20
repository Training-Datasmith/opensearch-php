<?php

declare (strict_types=1);
namespace Open_Search;

use Http\Discovery\Psr17factory_Discovery;
use Http\Discovery\Psr18client_Discovery;
use Open_Search\Serializers\Serializer_Interface;
use Open_Search\Serializers\Smart_Serializer;
use Psr\Http\Client\Client_Interface;
use Psr\Http\Message\Request_Factory_Interface as PsrRequestFactoryInterface;
use Psr\Http\Message\Stream_Factory_Interface;
use Psr\Http\Message\Uri_Factory_Interface;
/**
 * Creates a PSR transport falling back to a discovery mechanism if properties are not specified.
 */
class Transport_Factory
{
    private ?Psr_Request_Factory_Interface $psr_request_factory = null;
    private ?Stream_Factory_Interface $stream_factory = null;
    private ?Uri_Factory_Interface $uri_factory = null;
    private ?Serializer_Interface $serializer = null;
    private ?Request_Factory_Interface $request_factory = null;
    private ?Client_Interface $http_client = null;
    protected function get_http_client(): ?Client_Interface
    {
        return $this->http_client;
    }
    public function set_http_client(?Client_Interface $http_client): static
    {
        $this->http_client = $http_client;
        return $this;
    }
    protected function get_request_factory(): ?Request_Factory_Interface
    {
        return $this->request_factory;
    }
    public function set_request_factory(?Request_Factory_Interface $request_factory): static
    {
        $this->request_factory = $request_factory;
        return $this;
    }
    protected function get_psr_request_factory(): Psr_Request_Factory_Interface
    {
        if ($this->psr_request_factory === null) {
            $this->psr_request_factory = Psr17factory_Discovery::find_request_factory();
        }
        return $this->psr_request_factory;
    }
    public function set_psr_request_factory(Psr_Request_Factory_Interface $psr_request_factory): static
    {
        $this->psr_request_factory = $psr_request_factory;
        return $this;
    }
    protected function get_stream_factory(): Stream_Factory_Interface
    {
        if ($this->stream_factory === null) {
            $this->stream_factory = Psr17factory_Discovery::find_stream_factory();
        }
        return $this->stream_factory;
    }
    public function set_stream_factory(Stream_Factory_Interface $stream_factory): static
    {
        $this->stream_factory = $stream_factory;
        return $this;
    }
    protected function get_uri_factory(): Uri_Factory_Interface
    {
        if ($this->uri_factory === null) {
            $this->uri_factory = Psr17factory_Discovery::find_uri_factory();
        }
        return $this->uri_factory;
    }
    public function set_uri_factory(Uri_Factory_Interface $uri_factory): static
    {
        $this->uri_factory = $uri_factory;
        return $this;
    }
    protected function get_serializer(): Serializer_Interface
    {
        if ($this->serializer === null) {
            $this->serializer = new Smart_Serializer();
        }
        return $this->serializer;
    }
    public function set_serializer(Serializer_Interface $serializer): static
    {
        $this->serializer = $serializer;
        return $this;
    }
    /**
     * Creates a new transport.
     */
    public function create(): Http_Transport
    {
        if ($this->request_factory === null) {
            $psr_request_factory = $this->get_psr_request_factory();
            $stream_factory = $this->get_stream_factory();
            $uri_factory = $this->get_uri_factory();
            $serializer = $this->get_serializer();
            $this->request_factory = new Request_Factory($psr_request_factory, $stream_factory, $uri_factory, $serializer);
        }
        if ($this->http_client === null) {
            $this->http_client = Psr18client_Discovery::find();
        }
        return new Http_Transport($this->http_client, $this->request_factory, $this->get_serializer());
    }
}