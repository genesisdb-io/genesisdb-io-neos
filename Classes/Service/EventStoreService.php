<?php

declare(strict_types=1);

namespace GenesisDB\Neos\GenesisDB\Service;

/*
 * This file is part of the GenesisDB.Neos.GenesisDB package.
 */

use GenesisDB\Client;
use GenesisDB\Event;
use GenesisDB\FetchOptions;
use GenesisDB\NewEvent;
use GenesisDB\Precondition;
use GenesisDB\RegisteredSchema;
use GenesisDB\Status;
use Neos\Flow\Annotations as Flow;

/**
 * Flow access to GenesisDB 2.0 through the official PHP client.
 *
 * Configure GenesisDB.Neos.GenesisDB.url and .authToken. Empty values fall back to the
 * GENESISDB_URL and GENESISDB_AUTH_TOKEN environment variables.
 */
#[Flow\Scope("singleton")]
final class EventStoreService
{
    #[Flow\InjectConfiguration(path: 'url', package: 'GenesisDB.Neos.GenesisDB')]
    protected ?string $url = null;

    #[Flow\InjectConfiguration(path: 'authToken', package: 'GenesisDB.Neos.GenesisDB')]
    protected ?string $authToken = null;

    #[Flow\InjectConfiguration(path: 'timeout', package: 'GenesisDB.Neos.GenesisDB')]
    protected float|int|null $timeout = null;

    private ?Client $client = null;

    /**
     * The underlying client, created on first use, for calls this service does not wrap.
     */
    public function client(): Client
    {
        return $this->client ??= new Client(
            url: $this->url ?: null,
            token: $this->authToken ?: null,
            timeout: (float) ($this->timeout ?? 30.0),
        );
    }

    /**
     * Commit events atomically after checking the preconditions. Returns the stored events.
     *
     * @param list<NewEvent> $events
     * @param list<Precondition> $preconditions
     * @return list<Event>
     */
    public function commit(array $events, array $preconditions = []): array
    {
        return $this->client()->commit($events, $preconditions);
    }

    /**
     * Fetch the events of a subject and all of its descendants, in commit order.
     *
     * @return list<Event>
     */
    public function fetch(string $subject, ?FetchOptions $options = null): array
    {
        return $this->client()->fetch($subject, $options);
    }

    /**
     * Subscribe to a subject and its descendants. Yields the history first, then new events.
     *
     * @param float|null $interval poll interval in seconds, null uses the server default of one second
     * @return \Generator<int, Event>
     */
    public function subscribe(string $subject, ?float $interval = null, ?FetchOptions $options = null): \Generator
    {
        return $this->client()->subscribe($subject, $interval, $options);
    }

    /**
     * Run a GDBQL query. Returns the result rows, or a single-element list for a bare aggregate.
     *
     * @return list<mixed>
     */
    public function query(string $query): array
    {
        return $this->client()->query($query);
    }

    /**
     * Distinct top level subjects.
     *
     * @return list<string>
     */
    public function subjects(): array
    {
        return $this->client()->subjects();
    }

    /**
     * Distinct event types.
     *
     * @return list<string>
     */
    public function types(): array
    {
        return $this->client()->types();
    }

    /**
     * Register a JSON schema for an event type. Administrator token.
     *
     * @param array<string, mixed> $schema
     */
    public function registerSchema(string $eventType, array $schema): void
    {
        $this->client()->registerSchema($eventType, $schema);
    }

    /**
     * Every registered schema.
     *
     * @return list<RegisteredSchema>
     */
    public function schemas(): array
    {
        return $this->client()->schemas();
    }

    /**
     * Erase every reference payload below a subject. Administrator token.
     */
    public function erase(string $subject): string
    {
        return $this->client()->erase($subject);
    }

    /**
     * Verify the hash chain of the whole store. Administrator token.
     */
    public function audit(): string
    {
        return $this->client()->audit();
    }

    /**
     * Engine version, event counts, storage format and readiness.
     */
    public function status(): Status
    {
        return $this->client()->status();
    }

    /**
     * Liveness check. True when the server answers "Pong".
     */
    public function ping(): bool
    {
        return strcasecmp(trim($this->client()->ping()), 'pong') === 0;
    }
}
