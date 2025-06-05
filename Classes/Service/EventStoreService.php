<?php
namespace GenesisDB\Neos\GenesisDB\Service;

/*
 * This file is part of the GenesisDB.Neos.GenesisDB package.
 */

use GenesisDB\GenesisDB\Client ;
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
     * @return array
     */
    public function streamEvents(string $subject): array
    {
        return $this->eventStore()->streamEvents($subject);
    }

    /**
     * @param array $events
     * @return void
     */
    public function commitEvents(array $events): void
    {
        $this->eventStore()->commitEvents($events);
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
