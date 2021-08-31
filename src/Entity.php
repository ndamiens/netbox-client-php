<?php

namespace ND\Netbox;

class Entity implements \ArrayAccess {

    /** protect keys from updates unwanted */
    const NO_CHANGES_KEYS = ['id', 'url'];

    /** @var array */
    protected $entity;

    /** @var array */
    private $changes = [];

    /** @var Client */
    protected $client;

    public function __construct(Client $client, array $entity) {
        $this->entity = $entity;
        $this->client = $client;
    }

    public function offsetExists($offset): bool {
        return isset($this->entity[$offset]);
    }

    public function offsetGet($offset) {
        if (!$this->offsetExists($offset)) {
            throw new Exception("unknown key '$offset' possible values " . join(',', array_keys($this->entity)));
        }
        return $this->entity[$offset];
    }

    public function offsetSet($offset, $value): void {
        if (!$this->offsetExists($offset)) {
            throw new Exception("unknown key '$offset' possible values " . join(',', array_keys($this->entity)));
        }
        if (in_array($offset, self::NO_CHANGES_KEYS)) {
            throw new Exception("no changes allowed on $offset");
        }
        if ($this->entity[$offset] == $value) {
            // no changes
            return;
        }
        $this->entity[$offset] = $value;
        if (!in_array($offset, $this->changes)) {
            $this->changes[] = $offset;
        }
    }

    public function update(): void {
        $changes_with_values = [];
        foreach ($this->changes as $changed_field) {
            $changes_with_values[$changed_field] = $this->entity[$changed_field];
        }
        if (count($changes_with_values) <= 0) {
            return;
        }
        try {
            $this->client->getGuzzleClient()->patch(
                    $this->entity['url'],
                    ['json' => $changes_with_values]
            );
        } catch (\Exception $e) {
            throw new Exception("update failed");
        }
        $this->changes = [];
    }

    public function offsetUnset($offset): void {
        throw new Exception("unset not allowed");
    }

}
