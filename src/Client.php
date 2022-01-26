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

    public function getSite(int $site_id): Collection {
        $q = $this->getGuzzleClient()->request("GET", sprintf("%s/api/dcim/sites/?id=%d", $this->api_url, $site_id));
        return (new Collection($this, $q))->current();
    }

    public function getApiUrl(): string {
        return $this->api_url;
    }

}
