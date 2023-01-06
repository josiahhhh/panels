<?php

namespace Pterodactyl\Services\Servers;

use Pterodactyl\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class FilterService
{
    /**
     * @var string
     */
    protected static $username = 'icelinehosting';

    /**
     * @var string
     */
    protected static $password = 'RxWFjCmwyUoLxHRy';

    /**
     * @return false|mixed
     */
    public static function getFilters()
    {
        $request = self::handleRequest('GET', 'https://api.path.net/filters/available');

        if (!isset($request['filters'])) {
            return false;
        }

        return $request['filters'];
    }

    /**
     * @param Server $server
     * @param $filterType
     * @return bool
     */
    public static function createFilter(Server $server, $filterType)
    {
        $excludedNodeIds = DB::table('settings')->where('key', '=', 'settings::filters::excluded_node_ids')->first();
        if ($excludedNodeIds && in_array($server->node_id, explode(',', $excludedNodeIds->value))) {
            return false;
        }

        $allocation = $server->allocation()->first();
        // $allocation->ip = '23.183.246.236';

        $request = self::handleRequest('POST', sprintf('https://api.path.net/filters/%s', $filterType), [
            'addr' => $allocation->ip,
            'port' => $allocation->port,
            'tcp_port' => $allocation->port,
            'voice_port' => $allocation->port,
            'file_transfer_port' => $allocation->port,
            'accept_queries' => true,
            'antispoofing' => true,
        ]);

        if (!isset($request['id'])) {
            return false;
        }

        DB::table('servers')->where('id', '=', $server->id)->update([
            'filter' => json_encode([
                'type' => $filterType,
                'id' => $request['id'],
            ]),
        ]);

        return true;
    }

    /**
     * @param Server $server
     * @return bool
     */
    public static function deleteFilter(Server $server)
    {
        if (is_null($server->filter)) {
            return false;
        }

        $filter = json_decode($server->filter, true);

        $request = self::handleRequest('DELETE', sprintf('https://api.path.net/filters/%s/%s', $filter['type'], $filter['id']));
        if (!isset($request['acknowledged']) || $request['acknowledged'] != true) {
            return false;
        }

        return true;
    }

    /**
     * @param Server $server
     * @return array|bool[]
     */
    public static function createRules(Server $server)
    {
        $excludedNodeIds = DB::table('settings')->where('key', '=', 'settings::filters::excluded_node_ids')->first();
        if ($excludedNodeIds && in_array($server->node_id, explode(',', $excludedNodeIds->value))) {
            return ['success' => true];
        }

        $ids = [];
        $errors = [];
        $allocation = $server->allocation()->first();
        // $allocation->ip = '23.183.246.236';

        $requestUDP = self::handleRequest('POST', 'https://api.path.net/rules', [
            'destination' => $allocation->ip,
            'source' => '0.0.0.0/0',
            'protocol' => 'udp',
            'dst_port' => $allocation->port,
            'whitelist' => true,
            'priority' => false,
            'comment' => $server->uuid,
        ]);

        if (isset($requestUDP['id'])) {
            $ids[] = $requestUDP['id'];
        } else if (isset($requestUDP['detail'])) {
            $errors[] = $requestUDP['detail'];
        } else {
            $errors[] = print_r($requestUDP, true);
        }

        $requestTCP = self::handleRequest('POST', 'https://api.path.net/rules', [
            'destination' => $allocation->ip,
            'source' => '0.0.0.0/0',
            'protocol' => 'tcp',
            'dst_port' => $allocation->port,
            'whitelist' => true,
            'priority' => false,
            'comment' => $server->uuid,
        ]);

        if (isset($requestTCP['id'])) {
            $ids[] = $requestTCP['id'];
        } else if (isset($requestTCP['detail'])) {
            $errors[] = $requestTCP['detail'];
        } else {
            $errors[] = print_r($requestTCP, true);
        }

        DB::table('servers')->where('id', '=', $server->id)->update([
            'rules' => $ids,
        ]);

        return [
            'success' => count($errors) < 1,
            'errors' => $errors,
        ];
    }

    /**
     * @param Server $server
     * @return bool
     */
    public static function deleteRules(Server $server)
    {
        if (is_null($server->rules)) {
            return false;
        }

        foreach (json_decode($server->rules) as $ruleId) {
            self::handleRequest('DELETE', sprintf('https://api.path.net/rules/%s', $ruleId));
        }

        return true;
    }

    /**
     * @return array|false[]
     */
    private static function getAuthToken()
    {
        if (Cache::has('filters::auth')) {
            $request = self::handleRequest('GET', 'https://api.path.net/token/verify', [
                'token' => Cache::get('filters::auth'),
            ]);

            if (!isset($request['access_token'])) {
                Cache::delete('filters:auth');

                return self::getAuthToken();
            }
        } else {
            $request = self::handleRequest('POST', 'https://api.path.net/token', [
                'username' => self::$username,
                'password' => self::$password,
            ], false);

            if (!isset($request['access_token'])) {
                return ['valid' => false];
            }

            Cache::put('filters::auth', $request['access_token'], 86400);
        }

        return [
            'valid' => true,
            'token' => $request['access_token'],
        ];
    }

    /**
     * @param $method
     * @param $url
     * @param array $body
     * @param bool $auth
     * @return mixed
     */
    private static function handleRequest($method, $url, array $body = [], bool $auth = true)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Pterodactyl Panel');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

        $headers = [];

        if ($auth) {
            if (isset($body['token'])) {
                $headers[] = 'Authorization: Bearer ' . $body['token'];
            } else {
                $authToken = self::getAuthToken();

                if ($authToken['valid'] != true) {
                    return [];
                }

                $headers[] = 'Authorization: Bearer ' . $authToken['token'];
            }
        }

        if ($method == 'POST' || $method == 'PATCH') {
            if (!$auth) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));

                $headers[] = "Content-Type: application/x-www-form-urlencoded";
            } else {
                $jsonData = json_encode($body);

                curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);

                $headers[] = "Content-Type: application/json";
                $headers[] = "Content-Length: " . strlen($jsonData);
            }
        }

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $responseBody = json_decode($response, true);
        $responseBody['status_code'] = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $responseBody;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public static function getAllFilters()
    {
        $filters = DB::table('filters')->get();

        foreach ($filters as $key => $filter) {
            $eggNames = [];

            foreach (explode(',', $filter->egg_ids) as $eggId) {
                $egg = DB::table('eggs')->where('id', '=', $eggId)->first();
                if ($egg) {
                    $eggNames[] = $egg->name;
                }
            }

            $filters[$key]->eggs = implode(', ', $eggNames);
        }

        return $filters;
    }
}
