<?php
namespace GenesisDB\Neos\GenesisDB\Service;

/*
 * This file is part of the GenesisDB.Neos.GenesisDB package.
 */

use GenesisDB\GenesisDB\Client;
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
     * @param string $subject
     * @param string|null $lowerBound
     * @param bool|null $includeLowerBoundEvent
     * @param string|null $latestByEventType
     * @return array
     */
    public function streamEvents(string $subject, ?string $lowerBound = null, ?bool $includeLowerBoundEvent = null, ?string $latestByEventType = null): array
    {
        return $this->eventStore()->streamEvents($subject, $lowerBound, $includeLowerBoundEvent, $latestByEventType);
    }

    /**
     * @param string $subject
     * @param string|null $lowerBound
     * @param bool|null $includeLowerBoundEvent
     * @param string|null $latestByEventType
     * @return \Generator
     */
    public function observeEvents(string $subject, ?string $lowerBound = null, ?bool $includeLowerBoundEvent = null, ?string $latestByEventType = null): \Generator
    {
        return $this->eventStore()->observeEvents($subject, $lowerBound, $includeLowerBoundEvent, $latestByEventType);
    }

    /**
     * @param array $events
     * @param array|null $preconditions
     * @return void
     */
    public function commitEvents(array $events, ?array $preconditions = null): void
    {
        $this->eventStore()->commitEvents($events, $preconditions);
    }

    /**
     * Erase data for GDPR compliance
     * @param string $subject
     * @return void
     */
    public function eraseData(string $subject): void
    {
        $this->eventStore()->eraseData($subject);
    }

    /**
     * @param string $query
     * @return array
     */
    public function q(string $query): array
    {
        return $this->eventStore()->q($query);
    }

    /**
     * @return string
     */
    public function audit(): string
    {
        return $this->eventStore()->audit();
    }

    /**
     * @return bool
     */
    public function ping(): bool
    {
        return $this->eventStore()->ping() === 'pong';
    }

}
