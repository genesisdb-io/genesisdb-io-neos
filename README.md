# GenesisDB Neos Flow package

A package to enable the use of GenesisDB with Flow and Neos.

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
use GenesisDB\GenesisDB\CommitEvent;
use GenesisDB\GenesisDB\CommitEventOptions;
use GenesisDB\GenesisDB\Precondition;
use GenesisDB\GenesisDB\StreamOptions;
use Neos\Flow\Annotations as Flow;

#[Flow\Inject]
protected EventStoreService $eventStoreService;
```

## Streaming Events

### Basic Event Streaming

```php
// Stream all events for a subject
$events = $this->eventStoreService->streamEvents('/customer');
```

### Stream Events from Lower Bound

```php
$events = $this->eventStoreService->streamEvents('/customer', new StreamOptions(
    lowerBound: 'event-id-123',
    includeLowerBoundEvent: true
));
```

### Stream Events with Upper Bound

```php
$events = $this->eventStoreService->streamEvents('/customer', new StreamOptions(
    upperBound: '9f3e4141-7208-4fb2-905f-445730f4f3b1',
    includeUpperBoundEvent: false
));
```

### Stream Events with Both Lower and Upper Bounds

```php
$events = $this->eventStoreService->streamEvents('/customer', new StreamOptions(
    lowerBound: '2d6d4141-6107-4fb2-905f-445730f4f2a9',
    includeLowerBoundEvent: true,
    upperBound: '9f3e4141-7208-4fb2-905f-445730f4f3b1',
    includeUpperBoundEvent: false
));
```

### Stream Latest Events by Event Type

```php
$latestEvents = $this->eventStoreService->streamEvents('/customer', new StreamOptions(
    latestByEventType: 'io.genesisdb.app.customer-updated'
));
```

## Committing Events

### Basic Event Committing

```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/customer',
        type: 'io.genesisdb.app.customer-added',
        data: [
            'firstName' => 'Bruce',
            'lastName' => 'Wayne',
            'emailAddress' => 'bruce.wayne@enterprise.wayne'
        ]
    ),
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/customer',
        type: 'io.genesisdb.app.customer-added',
        data: [
            'firstName' => 'Alfred',
            'lastName' => 'Pennyworth',
            'emailAddress' => 'alfred.pennyworth@enterprise.wayne'
        ]
    ),
    new CommitEvent(
        source: 'io.genesisdb.store',
        subject: '/article',
        type: 'io.genesisdb.store.article-added',
        data: [
            'name' => 'Tumbler',
            'color' => 'black',
            'price' => 2990000.00
        ]
    ),
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/customer/fed2902d-0135-460d-8605-263a06308448',
        type: 'io.genesisdb.app.customer-personaldata-changed',
        data: [
            'firstName' => 'Angus',
            'lastName' => 'MacGyver',
            'emailAddress' => 'angus.macgyer@phoenix.foundation'
        ]
    )
]);
```

## Preconditions

Preconditions allow you to enforce certain checks on the server before committing events. GenesisDB supports multiple precondition types:

### isSubjectNew

Ensures that a subject is new (has no existing events):

```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/user/456',
        type: 'io.genesisdb.app.user-created',
        data: [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com'
        ]
    )
], [
    Precondition::isSubjectNew('/user/456')
]);
```

### isSubjectExisting

Ensures that events exist for the specified subject:

```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/user/456',
        type: 'io.genesisdb.app.user-updated',
        data: [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com'
        ]
    )
], [
    Precondition::isSubjectExisting('/user/456')
]);
```

### isQueryResultTrue

Evaluates a query and ensures the result is truthy. Supports the full GDBQL feature set including complex WHERE clauses, aggregations, and calculated fields.

**Basic uniqueness check:**
```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/user/456',
        type: 'io.genesisdb.app.user-created',
        data: [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com'
        ]
    )
], [
    Precondition::isQueryResultTrue(
        "STREAM e FROM events WHERE e.data.email == 'john.doe@example.com' MAP COUNT() == 0"
    )
]);
```

**Business rule enforcement (transaction limits):**
```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.banking',
        subject: '/user/123/transactions',
        type: 'io.genesisdb.banking.transaction-processed',
        data: [
            'amount' => 500.00,
            'currency' => 'EUR'
        ]
    )
], [
    Precondition::isQueryResultTrue(
        "STREAM e FROM events WHERE e.subject UNDER '/user/123' AND e.type == 'transaction-processed' AND e.time >= '2024-01-01T00:00:00Z' MAP SUM(e.data.amount) + 500 <= 10000"
    )
]);
```

**Generic precondition (forward-compatible with future server releases):**
```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/test',
        type: 'io.genesisdb.app.test-created',
        data: ['key' => 'value']
    )
], [
    Precondition::generic('someCustomFuturePrecondition', ['foo' => 'bar', 'baz' => 123])
]);
```

**Supported GDBQL Features in Preconditions:**
- WHERE conditions with AND/OR/IN/BETWEEN operators
- Hierarchical subject queries (UNDER, DESCENDANTS)
- Aggregation functions (COUNT, SUM, AVG, MIN, MAX)
- GROUP BY with HAVING clauses
- ORDER BY and LIMIT clauses
- Calculated fields and expressions
- Nested field access (e.data.address.city)
- String concatenation and arithmetic operations

If a precondition fails, the commit returns HTTP 412 (Precondition Failed) with details about which condition failed.

## GDPR Compliance

### Store Data as Reference

```php
$this->eventStoreService->commitEvents([
    new CommitEvent(
        source: 'io.genesisdb.app',
        subject: '/user/456',
        type: 'io.genesisdb.app.user-created',
        data: [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@example.com'
        ],
        options: new CommitEventOptions(storeDataAsReference: true)
    )
]);
```

### Delete Referenced Data

```php
$this->eventStoreService->eraseData('/user/456');
```

## Observing Events

### Basic Event Observation

```php
foreach ($this->eventStoreService->observeEvents('/customer') as $event) {
    echo "Received event: " . $event->getType() . "\n";
    echo "Data: " . json_encode($event->getData()) . "\n";
}
```

### Observe Events from Lower Bound (Message Queue)

```php
foreach ($this->eventStoreService->observeEvents('/customer', new StreamOptions(
    lowerBound: 'event-id-123',
    includeLowerBoundEvent: true
)) as $event) {
    echo "Received event: " . $event->getType() . "\n";
}
```

### Observe Events with Upper Bound (Message Queue)

```php
foreach ($this->eventStoreService->observeEvents('/customer', new StreamOptions(
    upperBound: '9f3e4141-7208-4fb2-905f-445730f4f3b1',
    includeUpperBoundEvent: false
)) as $event) {
    echo "Received event: " . $event->getType() . "\n";
}
```

### Observe Events with Both Bounds (Message Queue)

```php
foreach ($this->eventStoreService->observeEvents('/customer', new StreamOptions(
    lowerBound: '2d6d4141-6107-4fb2-905f-445730f4f2a9',
    includeLowerBoundEvent: true,
    upperBound: '9f3e4141-7208-4fb2-905f-445730f4f3b1',
    includeUpperBoundEvent: false
)) as $event) {
    echo "Received event: " . $event->getType() . "\n";
}
```

### Observe Latest Events by Event Type (Message Queue)

```php
foreach ($this->eventStoreService->observeEvents('/customer', new StreamOptions(
    latestByEventType: 'io.genesisdb.app.customer-updated'
)) as $event) {
    echo "Received latest event: " . $event->getType() . "\n";
}
```

## Querying Events

```php
$results = $this->eventStoreService->queryEvents(
    'STREAM e FROM events WHERE e.type == "io.genesisdb.app.customer-added" ORDER BY e.time DESC LIMIT 20 MAP { subject: e.subject, firstName: e.data.firstName }'
);

foreach ($results as $result) {
    echo "Result: " . json_encode($result) . "\n";
}
```

## Health Checks

```php
// Check API status
$isAlive = $this->eventStoreService->ping();
echo "Alive: " . ($isAlive ? 'yes' : 'no') . "\n";

// Run audit to check event consistency
$auditResponse = $this->eventStoreService->audit();
echo "Audit response: " . $auditResponse . "\n";
```

## Author

* E-Mail: mail@genesisdb.io
* URL: https://www.genesisdb.io
