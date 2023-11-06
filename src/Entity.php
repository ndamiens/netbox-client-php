<?php

namespace ND\Netbox;

use GuzzleHttp\Exception\ClientException;

class Entity implements \ArrayAccess
{

    /** protect keys from updates unwanted */
    const NO_CHANGES_KEYS = ['id', 'url'];

    /** @var array */
    protected $entity;

    /** @var array */
    private $changes = [];

    /** @var Client */
    protected $client;

    public function __construct(Client $client, array $entity)
    {
        $this->entity = $entity;
        $this->client = $client;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->entity[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new Exception("unknown key '$offset' possible values " . join(',', array_keys($this->entity)));
        }
        return $this->entity[$offset];
    }

    public function offsetSet($offset, $value): void
    {
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

    public function updateRelation($key, $value)
    {
        try {
            $this->client->getGuzzleClient()->patch(
                $this->entity['url'],
                [
                    'json' => [$key => $value]
                ]
            );
        } catch (\Exception $e) {
            throw new Exception("update failed " . $e->getMessage());
        }
    }

    public function update(): void
    {
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
            throw new Exception("update failed " . $e->getMessage());
        }
        $this->changes = [];
    }

    /**
     * Post vers Netbox
     * @param Client $client
     * @param string $path
     * @param array $values
     * @throw ClientException
     * @return void
     */
    public static function post(Client $client, string $path, array $values): int
    {
        $response = $client->getGuzzleClient()->post(
            $client->getApiUrl() . $path,
            ['json' => $values]
        );
        $resp = json_decode($response->getBody()->getContents(), true);
        if (empty($resp['id'])) {
            // c'est peut $etre une liste
            if (empty($resp[0]['id'])) {
                throw new \Exception("pas d'id en réponse :" . json_encode($resp, \JSON_PRETTY_PRINT));
            } else {
                $resp = $resp[0];
            }
        }
        return $resp['id'];
    }

    /**
     * Create tenant
     * @param Client $client
     * @param array $values required keys name, slug
     * @throw ClientException
     */
    public static function postTenant(Client $client, array $values): void
    {
        return self::post($client, "/api/tenancy/tenants/", $values);
    }

    /**
     * Create Vlangroup
     * @param Client $client
     * @param array $values 
     * @throw ClientException
     */
    public static function postVlanGroup(Client $client, array $values): void
    {
        return self::post($client, "/api/ipam/vlan-groups/", $values);
    }

    const VLAN_STATUS_LIST = ['active', 'reserved', 'deprecated'];

    /**
     * Create Vlan
     * @param Client $client
     * @param array $values required keys : vid, name optional : group, tenant, status 
     * @throw ClientException
     * 
     * status values : active, reserved, deprecated
     */
    public static function postVlan(Client $client, array $values): void
    {
        if (array_key_exists('status', $values)) {
            if (!in_array($values['status'], self::VLAN_STATUS_LIST)) {
                throw new Exception('invalid status');
            }
        }
        return self::post($client, "/api/ipam/vlans/", $values);
    }

    public function offsetUnset($offset): void
    {
        throw new Exception("unset not allowed");
    }
}
