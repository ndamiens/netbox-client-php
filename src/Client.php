<?php

namespace ND\Netbox;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;

class Client {

    private $g_client;
    protected $api_url;
    protected $token;

    public function __construct(string $api_url, string $token) {
        $this->api_url = trim($api_url, "/");
        $this->token = $token;
    }

    public function getGuzzleClient(): GuzzleClient {
        $this->g_client = new GuzzleClient(
                [
            "headers" => [
                "Authorization" => "Token {$this->token}",
            ]
                ]
        );
        return $this->g_client;
    }

    /**
     * GET an url
     * @param string $url
     * @return ResponseInterface
     */
    public function getUrl(string $url): ResponseInterface {
        return $this->getGuzzleClient()->get($url);
    }

    /**
     * Gets a NetBox API and returns a Collection from it
     * @param string $url
     * @return Collection
     */
    public function getCollection($url): Collection {
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s%s", $this->api_url, $url));
        return new Collection($this, $q);
    }

    /**
     * Gets a NetBox API which returns a collection of one, and returns the Entity from it
     */
    public function getSingle($url): Entity {
        $collection = $this->getCollection($url);
        return $collection->current();
    }

    public function devices(?array $device_roles = null): Collection {
        $roles = [];
        if (is_array($device_roles)) {
            foreach ($device_roles as $id) {
                $roles[] = sprintf("role_id=%d", $id);
            }
        }
        $q = $this->getGuzzleClient()->request("GET", $this->api_url . "/api/dcim/devices/?" . join("&", $roles));
        return new Collection($this, $q);
    }

    public function deviceInterfaces(int $device_id): Collection {
        return $this->getCollection(sprintf("/api/dcim/interfaces/?device_id=%d", $device_id));
    }

    public function getInterface(int $interface_id): Entity {
        return $this->getSingle(sprintf("/api/dcim/interfaces/%d", $interface_id));
    }

    public function getTenants(): Collection {
        return $this->getCollection("/api/tenancy/tenants/");
    }

    public function getTenant(int $tenant_id): Entity {
        return $this->getSingle(sprintf("/api/tenancy/tenants/%d", $tenant_id));
    }

    public function getSites(): Collection {
        return $this->getCollection("/api/dcim/sites/");
    }

    public function getSite(int $site_id): Entity {
        return $this->getSingle(sprintf("/api/dcim/sites/%d", $site_id));
    }

    public function getVlan(int $vlan_id): Entity {
        return $this->getSingle(sprintf("/api/ipam/vlans/%d", $vlan_id));
    }

    public function getApiUrl(): string {
        return $this->api_url;
    }

    /**
     * Group vlans list
     * @param int $group_id
     * @return Collection
     */
    public function groupVlans(int $group_id): Collection {
        return $this->getCollection(sprintf("/api/ipam/vlans/?group_id=%d", $group_id));
    }

    /**
     * Get max vlan id in group
     * @param int $group_id
     * @return int|null
     */
    public function getMaxVidInVlanGroup(int $group_id): ?int {
        $last_id = 1;
        foreach ($this->groupVlans($group_id) as $vlan) {
            $last_id = max($last_id, $vlan['vid']);
        }
        return $last_id;
    }

    /**
     * Get next vid in group
     * @param int $group_id
     * @return int|null
     * @throws Exception
     */
    public function getNextVidInVlanGroup(int $group_id): ?int {
        $next_vid = $this->getMaxVidInVlanGroup($group_id) + 1;
        if ($next_vid >= 4096) {
            throw new Exception("free vlans first");
        }
        return $this->getMaxVidInVlanGroup($group_id) + 1;
    }

    /**
     * Device cables list
     * @param int $cable_id
     * @return Collection
     */
    public function deviceCables(int $device_id): Collection {
        return $this->getCollection(sprintf("/api/dcim/cables/?device_id=%d", $device_id));
    }

}
