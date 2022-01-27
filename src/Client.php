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
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/dcim/interfaces/?device_id=%d", $this->api_url, $device_id));
        return new Collection($this, $q);
    }

    public function getTenants(): Collection {
        $q = $this->getGuzzleClient()->request("GET", $this->api_url . "/api/tenancy/tenants");
        return new Collection($this, $q);
    }

    public function getTenant(int $tenant_id): Entity {
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/tenancy/tenants/?id=%d", $this->api_url, $tenant_id));
        return (new Collection($this, $q))->current();
    }

    public function getSites(): Collection {
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/dcim/sites", $this->api_url));
        return new Collection($this, $q);
    }

    /**
     * Get one site by id
     * @param int $site_id
     * @return Entity
     */
    public function getSite(int $site_id): Entity {
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/dcim/sites/?id=%d", $this->api_url, $site_id));
        return (new Collection($this, $q))->current();
    }

    /**
     * Get one vlan by id (netbox)
     * @param int $vlan_id
     * @return Entity
     */
    public function getVlan(int $vlan_id): Entity {
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/ipam/vlans/?id=%d", $this->api_url, $vlan_id));
        return (new Collection($this, $q))->current();
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
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/ipam/vlans/?group_id=%d", $this->api_url, $group_id));
        return (new Collection($this, $q));
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

}
