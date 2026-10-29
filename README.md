# GenesisDB for Neos Flow

Use [GenesisDB](https://www.genesisdb.io), the open source event sourcing database, in Neos and Flow. The package wraps the official [GenesisDB PHP client](https://github.com/genesisdb-io/genesisdb-io-client-php) in a configurable Flow singleton.

> **Version 2.0.0 targets GenesisDB 2.0 and the 2.0 PHP client.** Configuration keys, type names and method names have changed, see [Upgrading from 1.x](#upgrading-from-1x). Existing 1.x installations keep working against a GenesisDB 2.0 server, which still serves the unchanged 1.x API under `/api/v1`.

Requirements: PHP 8.2+, Neos Flow 8 or 9, GenesisDB 2.0.

## Installation

```bash
composer require genesisdb/neos-genesisdb
```

## Configuration

```yaml
GenesisDB:
  Neos:
    GenesisDB:
      url: 'https://genesisdb.example.com'
      authToken: '%env:GENESISDB_AUTH_TOKEN%'
      timeout: 30
```

- `url` and `authToken` fall back to the `GENESISDB_URL` and `GENESISDB_AUTH_TOKEN` environment variables when empty.
- GenesisDB 2.0 requires tokens of at least 32 bytes, generate one with `openssl rand -hex 32`. Keep tokens out of version control.
- `timeout` applies to non-streaming calls in seconds. Subscriptions never time out.

## Usage

```php
use GenesisDB\Neos\GenesisDB\Service\EventStoreService;
use Neos\Flow\Annotations as Flow;

#[Flow\Inject]
protected EventStoreService $genesisDb;
```

All types come from the PHP client in the `GenesisDB` namespace: `NewEvent`, `Event`, `FetchOptions`, `Precondition`, `Status` and the exceptions.

### Commit events

```php
use GenesisDB\NewEvent;

// The batch is committed atomically. The server returns the stored events with ID, time and hashes.
$stored = $this->genesisDb->commit([
    new NewEvent('io.example.shop', '/customer/42', 'io.example.shop.customer-registered', [
        'firstName' => 'Bruce',
        'lastName' => 'Wayne',
    ]),
    new NewEvent('io.example.shop', '/order/1', 'io.example.shop.order-placed', ['total' => 99.5]),
]);

$stored[0]->id;
```

A subject with a single segment such as `/customer` gets a generated ID appended.

### Fetch events

```php
use GenesisDB\FetchOptions;

// Every event of the subject and its descendants, in commit order
$events = $this->genesisDb->fetch('/customer');

// Bounded by event IDs
$events = $this->genesisDb->fetch('/customer', new FetchOptions(
    lowerBound: '2d6d4141-6107-4fb2-905f-445730f4f2a9',
    includeLowerBoundEvent: true,
    upperBound: '9f3e4141-7208-4fb2-905f-445730f4f3b1',
    includeUpperBoundEvent: false,
));

// Only the newest event of a type
$latest = $this->genesisDb->fetch('/customer/42', new FetchOptions(
    latestByEventType: 'io.example.shop.customer-registered',
));
```

### Subscribe

```php
// The history first, then new events as they are committed. Break out of the loop to stop.
foreach ($this->genesisDb->subscribe('/customer', interval: 0.5) as $event) {
    echo $event->type, ' ', json_encode($event->data), PHP_EOL;
}
```

### Query with GDBQL

```php
$rows = $this->genesisDb->query(
    "STREAM e FROM events WHERE e.type == 'io.example.shop.customer-registered' ORDER BY e.time DESC LIMIT 20 MAP { subject: e.subject, firstName: e.data.firstName }"
);

[$count] = $this->genesisDb->query("STREAM e FROM events WHERE e.subject UNDER '/order' MAP COUNT()");
```

See the [GDBQL reference](https://docs.genesisdb.io/gdbql).

### Preconditions

Preconditions are checked atomically with the commit. A failed precondition throws `PreconditionFailedException` and writes nothing.

```php
use GenesisDB\Precondition;
use GenesisDB\PreconditionFailedException;

try {
    $this->genesisDb->commit(
        [new NewEvent('io.example.shop', '/user/456', 'io.example.shop.user-created', ['email' => 'john@example.com'])],
        [
            Precondition::isSubjectNew('/user/456'),
            Precondition::isQueryResultTrue("STREAM e FROM events WHERE e.data.email == 'john@example.com' MAP COUNT() == 0"),
        ],
    );
} catch (PreconditionFailedException $e) {
    $e->preconditionType; // for example "isSubjectNew"
    $e->reason;           // the server's explanation
}
```

`Precondition::isSubjectExisting('/user/456')` holds when the subject or a descendant has events.

### Personal data and erasure

```php
// Store the payload as reference data, so it can be erased later without breaking the hash chain
$this->genesisDb->commit([
    (new NewEvent('io.example.shop', '/user/456', 'io.example.shop.user-created', [
        'email' => 'john@example.com',
    ]))->asReference(),
]);

// Replace every reference payload below the subject with {}. Administrator token.
$this->genesisDb->erase('/user/456');
```

Erasure covers reference payloads only. Do not put personal data in subjects, types or sources.

### Schemas

```php
$this->genesisDb->registerSchema('io.example.shop.user-created', [
    'type' => 'object',
    'required' => ['email'],
    'properties' => ['email' => ['type' => 'string']],
]);

$schemas = $this->genesisDb->schemas();
```

### Catalog, status and audit

```php
$this->genesisDb->subjects();   // top level subjects
$this->genesisDb->types();      // event types
$this->genesisDb->ping();       // true when the server answers
$this->genesisDb->status();     // version, event counts, storage format, readiness
$this->genesisDb->audit();      // verifies the hash chain, administrator token
```

For anything the service does not wrap, `$this->genesisDb->client()` returns the underlying `GenesisDB\Client`.

## Upgrading from 1.x

| 1.x | 2.0 |
|---|---|
| `apiUrl` | `url` |
| `apiVersion` | removed, the client always uses API v2 |
| `authToken` | `authToken`, now at least 32 bytes |
| `streamEvents()` | `fetch()` |
| `observeEvents()` | `subscribe()` |
| `commitEvents()` | `commit()`, returns the stored events |
| `q()`, `queryEvents()` | `query()` |
| `eraseData()` | `erase()` |
| `GenesisDB\GenesisDB\CommitEvent` | `GenesisDB\NewEvent` |
| `GenesisDB\GenesisDB\StreamOptions` | `GenesisDB\FetchOptions` |
| `CommitEventOptions(storeDataAsReference: true)` | `->asReference()` |
| `Precondition::generic()` | removed |

GenesisDB 2.0 itself migrates an existing data directory on first start. The migration is one-way, so a backup of the data directory before upgrading is mandatory. See [Upgrade from 1.x](https://docs.genesisdb.io/upgrade).

## License

MIT, see [LICENSE](LICENSE).
