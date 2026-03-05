<?php
namespace GenesisDB\Neos\GenesisDB\Service;

/*
 * This file is part of the GenesisDB.Neos.GenesisDB package.
 */

use GenesisDB\GenesisDB\Client;
use GenesisDB\GenesisDB\CommitEvent;
use GenesisDB\GenesisDB\Precondition;
use GenesisDB\GenesisDB\StreamOptions;
use Neos\Flow\Annotations as Flow;

#[Flow\Scope("singleton")]
final class EventStoreService
{

    #[Flow\InjectConfiguration(path: 'apiUrl', package: 'GenesisDB.Neos.GenesisDB')]
    protected string $apiUrl;

    #[Flow\InjectConfiguration(path: 'apiVersion', package: 'GenesisDB.Neos.GenesisDB')]
    protected string $apiVersion;

    #[Flow\InjectConfiguration(path: 'authToken', package: 'GenesisDB.Neos.GenesisDB')]
    protected string $authToken;

    /**
     * @return Client
     */
    private function eventStore(): Client
    {
        return new Client($this->apiUrl, $this->apiVersion, $this->authToken);
    }

    /**
     * Stream events for a given subject
     *
     * @param string $subject
     * @param StreamOptions|null $options
     * @return array
     */
    public function streamEvents(string $subject, ?StreamOptions $options = null): array
    {
        return $this->eventStore()->streamEvents($subject, $options);
    }

    /**
     * Observe events for a given subject in real-time
     *
     * @param string $subject
     * @param StreamOptions|null $options
     * @return \Generator
     */
    public function observeEvents(string $subject, ?StreamOptions $options = null): \Generator
    {
        return $this->eventStore()->observeEvents($subject, $options);
    }

    /**
     * Commit events to GenesisDB
     *
     * @param CommitEvent[] $events
     * @param Precondition[]|null $preconditions
     * @return void
     */
    public function commitEvents(array $events, ?array $preconditions = null): void
    {
        $this->eventStore()->commitEvents($events, $preconditions);
    }

    /**
     * Erase data for GDPR compliance
     *
     * @param string $subject
     * @return void
     */
    public function eraseData(string $subject): void
    {
        $this->eventStore()->eraseData($subject);
    }

    /**
     * Execute a GDBQL query
     *
     * @param string $query
     * @return array
     */
    public function q(string $query): array
    {
        return $this->eventStore()->q($query);
    }

    /**
     * Query events using the same functionality as the q method
     *
     * @param string $query
     * @return array
     */
    public function queryEvents(string $query): array
    {
        return $this->eventStore()->queryEvents($query);
    }

    /**
     * Run audit to check event consistency
     *
     * @return string
     */
    public function audit(): string
    {
        return $this->eventStore()->audit();
    }

    /**
     * Health check
     *
     * @return bool
     */
    public function ping(): bool
    {
        return $this->eventStore()->ping() === 'pong';
    }

}
