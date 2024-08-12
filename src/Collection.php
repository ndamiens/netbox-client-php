<?php

namespace ND\Netbox;

use Psr\Http\Message\ResponseInterface;

class Collection implements \Iterator, \Countable
{

    protected $count = 0;
    protected $index = 0;
    protected $page_index = 0;
    protected $page;
    protected $page_org;
    protected $next = null;
    protected $next_org = null;

    /** @var Client */
    protected $client;

    public function __construct(Client $client, ResponseInterface $response)
    {
        if ($response->getStatusCode() != 200) {
            throw new Exception("netbox err" . $response->getStatusCode() . " " . $response->getReasonPhrase());
        }
        $resp = json_decode($response->getBody()->getContents(), true);
        $this->count = $resp['count'];
        $this->page = $resp['results'];
        $this->next = $resp['next'];
        $this->client = $client;
        $this->page_org = $this->page;
        $this->next_org = $this->next;
    }

    public function current(): Entity
    {
        return new Entity($this->client, $this->page[$this->page_index]);
    }

    public function key(): int
    {
        return $this->index;
    }

    public function next(): void
    {
        $this->index++;
        $this->page_index++;
        if (!$this->valid() && !is_null($this->next)) {
            $query = $this->client->getUrl($this->next);
            if ($query->getStatusCode() != 200) {
                return;
            }
            $resp = json_decode($query->getBody()->getContents(), true);
            $this->next = $resp['next'];
            $this->page = $resp['results'];
            $this->page_index = 0;
        }
    }

    public function rewind(): void
    {
        $this->index = 0;
        $this->page_index = 0;
        $this->page = $this->page_org;
        $this->next = $this->next_org;
    }

    public function valid(): bool
    {
        return isset($this->page[$this->page_index]);
    }

    public function count(): int
    {
        return $this->count;
    }
}
