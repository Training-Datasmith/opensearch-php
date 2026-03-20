<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use OpenSearch\ClientBuilder;

// --- Example 1: Connect and check cluster health ---
$client = ClientBuilder::create()
    ->setHosts(['https://localhost:9200'])
    ->setBasicAuthentication('admin', 'admin')  // replace with real credentials
    ->setSSLVerification(false)                 // disable only in dev; use certificates in production
    ->build();

// Check cluster health
$health = $client->cluster()->health();
echo "Cluster status: " . $health['status'] . "\n\n";

// --- Example 2: Index a document ---
$response = $client->index([
    'index' => 'products',
    'id'    => '1',
    'body'  => [
        'name'        => 'PHP Programming Book',
        'description' => 'A comprehensive guide to PHP development',
        'price'       => 49.99,
        'tags'        => ['php', 'programming', 'web'],
    ],
]);
echo "Indexed document: " . $response['result'] . "\n\n";

// --- Example 3: Search with a query ---
$results = $client->search([
    'index' => 'products',
    'body'  => [
        'query' => [
            'multi_match' => [
                'query'  => 'PHP programming',
                'fields' => ['name^2', 'description', 'tags'],
            ],
        ],
        'size' => 10,
    ],
]);

echo "Total hits: " . $results['hits']['total']['value'] . "\n";
foreach ($results['hits']['hits'] as $hit) {
    echo "  - " . $hit['_source']['name'] . " (score: " . $hit['_score'] . ")\n";
}
echo "\n";

// --- Example 4: Delete a document ---
$client->delete(['index' => 'products', 'id' => '1']);
echo "Document deleted.\n";
