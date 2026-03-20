# Architecture: opensearch-php

## Purpose

The official PHP client for OpenSearch. Provides a fluent API for all OpenSearch REST endpoints (index, search, cat, cluster, nodes, etc.) with connection pooling, retry logic, and AWS SigV4 signing support.

## Directory Structure

```
src/OpenSearch/
  Client.php                    — Main entry point; magic-property access to namespace clients
  Client_Builder.php            — Fluent builder: configure hosts, auth, SSL, pool, logger
  Client_Factory_Interface.php  — Interface for DI-friendly client construction
  Endpoint_Interface.php        — Interface all endpoint classes implement
  Endpoint_Factory.php          — Instantiates endpoint classes by name
  Endpoints/
    Abstract_Endpoint.php       — Base: holds params, URI building, HTTP method selection
    Bulk.php, Search.php, Index.php, ...  — One class per REST endpoint
    Cat/, Cluster/, Indices/, Nodes/, ...  — Namespaced endpoint groups
  ConnectionPool/
    Abstract_Connection_Pool.php — Base pool: tracks live/dead connections, pings
    Static_Connection_Pool.php   — Round-robin over a fixed list of hosts
    Sniffing_Connection_Pool.php — Discovers cluster nodes via the nodes/info API
    Simple_Connection_Pool.php   — Single-node pool
    Selectors/
      Round_Robin_Selector.php
      Random_Selector.php
      Sticky_Round_Robin_Selector.php
  Connections/
    Connection.php              — Wraps a Guzzle client; performs HTTP requests with retry/backoff
    Connection_Factory.php      — Builds Connection objects from host config
  Aws/
    Signing_Client_Decorator.php — Wraps Guzzle with AWS SigV4 request signing
    Signing_Client_Factory.php
  Common/
    Empty_Logger.php            — No-op PSR-3 logger
    Exceptions/                 — HTTP-status-code-specific exceptions
```

## Key Design Decisions

- **Namespace clients via magic properties**: `$client->indices()`, `$client->cluster()`, etc. return lazily-constructed namespace clients (e.g., `IndicesNamespace`) that group related endpoints
- **Endpoint objects**: Each API call is represented by an `AbstractEndpoint` subclass that validates params, builds the URI, and declares the HTTP method — keeping `Client` thin
- **Connection pool abstraction**: The pool decides which node to send each request to; `SniffingConnectionPool` automatically discovers new nodes
- **Dead-node tracking**: Failed requests mark a connection as "dead" with exponential backoff; `ping()` is used to revive them
- **AWS SigV4**: Achieved by decorating the Guzzle client with `SigningClientDecorator`, which signs requests before they are sent

## Extension Points

- Implement `Connection_Pool_Interface` for custom load-balancing strategies
- Implement `Selector_Interface` for custom node-selection algorithms
- Wrap the Guzzle client with additional middleware (e.g., logging, circuit breakers)

## Dependency Flow

```
Client
  → Endpoint (one per API operation)
  → ConnectionPool → Selector → Connection
      → Guzzle HTTP Client  [→ AWS SigV4 signing if configured]
      → OpenSearch cluster node
```
