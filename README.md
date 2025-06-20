# Genesis DB Neos Flow package

A package to enable the use of Genesis DB with Flow and Neos.

## Installation

Just run:

```
composer require genesisdb/neos-genesisdb
```

## Configuration

```yaml
GenesisDB:
  Neos:
    GenesisDB:
      apiUrl: 'https://genesisdb.domain.tld'
      apiVersion: 'v1'
      authToken: '21add3d2-6efb-4589-9305-e55925e77c43'
```

## Usage
```php
use GenesisDB\Neos\GenesisDB\Service\EventStoreService;

#[Flow\Inject]
protected EventStoreService $eventStoreService;

// Use the EventStore methods

// Stream events (array of CloudEvents)
$events = $this->eventStoreService->streamEvents('/customer');
        
// Commit events
$events = [
    [
        'source' => 'io.genesisdb.app',
        'subject' => '/customer',
        'type' => 'io.genesisdb.app.customer-added',
        'data' => [
            'firstName' => 'Bruce',
            'lastName' => 'Wayne',
            'emailAddress' => 'bruce.wayne@enterprise.wayne'
        ]
    ],
    [
        'source' => 'io.genesisdb.app',
        'subject' => '/customer',
        'type' => 'io.genesisdb.app.customer-added',
        'data' => [
            'firstName' => 'Alfred',
            'lastName' => 'Pennyworth',
            'emailAddress' => 'alfred.pennyworth@enterprise.wayne'
        ]
    ],
    [
        'source' => 'io.genesisdb.store',
        'subject' => '/article',
        'type' => 'io.genesisdb.store.article-added',
        'data' => [
            'name' => 'Tumbler',
            'color' => 'black',
            'price' => 2990000.00
        ]
    ],
    [
        'source' => 'io.genesisdb.app',
        'subject' => '/customer/fed2902d-0135-460d-8605-263a06308448',
        'type' => 'io.genesisdb.app.customer-personaldata-changed',
        'data' => [
            'firstName' => 'Angus',
            'lastName' => 'MacGyer',
            'emailAddress' => 'angus.macgyer@phoenix.foundation'
        ]
    ]
];
$this->eventStoreService->commitEvents($events);

$observed = $this->eventStoreService->observeEvents('/customer');

// Use the EventStore status methods
$this->eventStoreService->audit();
$this->eventStoreService->ping();
```


## Author

* E-Mail: mail@genesisdb.io
* URL: https://www.genesisdb.io
