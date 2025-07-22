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

// Stream events with lower bound
$events = $this->eventStoreService->streamEvents('/customer', 'event-id-123', true);

// Stream latest events by event type
$latestEvents = $this->eventStoreService->streamEvents('/customer', null, null, 'io.genesisdb.app.customer-updated');

// Commit events (options are optional per event)
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
        // No options - data stored normally
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

// Commit events with GDPR compliance (store data as reference)
$eventsWithGDPR = [
    [
        'source' => 'io.genesisdb.app',
        'subject' => '/customer/sensitive',
        'type' => 'io.genesisdb.app.customer-sensitive-data',
        'data' => [
            'ssn' => '123-45-6789',
            'creditCard' => '4111-1111-1111-1111'
        ],
        'options' => ['storeDataAsReference' => true]
    ]
];
$this->eventStoreService->commitEvents($eventsWithGDPR);

// Erase data for GDPR compliance
$this->eventStoreService->eraseData('/customer/sensitive');

$observed = $this->eventStoreService->observeEvents('/customer');

// Observe events with lower bound
foreach ($this->eventStoreService->observeEvents('/customer', 'event-id-123', true) as $event) {
    echo "Received event: " . $event->getType() . "\n";
}

// Use the EventStore status methods
$this->eventStoreService->audit();
$this->eventStoreService->ping();
```

## Examples

### GDPR Compliance

```php
// Store sensitive data as reference (options are optional per event)
$events = [
    [
        'source' => 'io.genesisdb.app',
        'subject' => '/user/123',
        'type' => 'io.genesisdb.app.user-created',
        'data' => ['email' => 'user@example.com', 'name' => 'John Doe'],
        'options' => ['storeDataAsReference' => true]  // ✅ Optional per event
    ],
    [
        'source' => 'io.genesisdb.app',
        'subject' => '/user/124',
        'type' => 'io.genesisdb.app.user-created',
        'data' => ['email' => 'user2@example.com', 'name' => 'Jane Doe']
        // ✅ No options - data stored normally
    ]
];

$this->eventStoreService->commitEvents($events);

// Erase user data for GDPR compliance
$this->eventStoreService->eraseData('/user/123');
```

### Enhanced Streaming

```php
// Stream events from a specific lower bound
$events = $this->eventStoreService->streamEvents('/user/123', 'event-id-123', true);

// Get latest events by event type
$latestEvents = $this->eventStoreService->streamEvents('/user/123', null, null, 'io.genesisdb.app.user-updated');

// Observe events with lower bound
foreach ($this->eventStoreService->observeEvents('/user/123', 'event-id-123', true) as $event) {
    echo "Received event: " . $event->getType() . "\n";
}
```

## Author

* E-Mail: mail@genesisdb.io
* URL: https://www.genesisdb.io
