<?php
/**
 * Minimal Name.com v4 REST API client — just the DNS-record operations the
 * dyndns endpoint needs.
 *
 * Name.com retired the old v1 session API (https://api.name.com/api, now HTTP
 * 410 Gone) that this file used to target; v4 replaces it. Auth is HTTP Basic
 * with the account username + an API token (no login/session step).
 *
 * Docs: https://www.name.com/api-docs/DNS
 */
class NameComApi
{
    private $base = 'https://api.name.com/v4';
    private $user;
    private $token;

    /** HTTP status of the most recent request (int), for callers to inspect. */
    public $last_status = 0;
    /** Transport-level error string, if curl itself failed. */
    public $last_error = null;

    /** Store credentials. Kept named login() for call-site familiarity — v4 has
     *  no real login/session; every request carries Basic auth. */
    public function login($username, $token)
    {
        $this->user = $username;
        $this->token = $token;
    }

    private function request($method, $path, $body = null)
    {
        $ch = curl_init($this->base . $path);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->user . ':' . $this->token,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 20,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }
        $raw = curl_exec($ch);
        $this->last_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $this->last_error = ($raw === false) ? curl_error($ch) : null;
        curl_close($ch);
        return $raw === false ? null : json_decode($raw);
    }

    /** True if the authenticated account manages this domain. */
    public function owns_domain($domain)
    {
        $this->request('GET', '/domains/' . rawurlencode($domain));
        return $this->last_status === 200;
    }

    /**
     * All DNS records for the domain as an array of objects
     * (id, host, fqdn, type, answer, ttl), or null on error.
     */
    public function list_records($domain)
    {
        $res = $this->request('GET', '/domains/' . rawurlencode($domain) . '/records');
        if ($this->last_status !== 200 || !isset($res->records)) {
            return null;
        }
        return $res->records;
    }

    /** Delete one record by its numeric id. Returns true on success. */
    public function delete_record($domain, $id)
    {
        $this->request('DELETE', '/domains/' . rawurlencode($domain) . '/records/' . (int) $id);
        return $this->last_status === 200;
    }

    /**
     * Create a record. $host is the label relative to $domain (e.g. "foo.dyndns"
     * for foo.dyndns.example.com; "" for the apex). Returns true on success.
     */
    public function create_record($domain, $host, $type, $answer, $ttl)
    {
        $this->request('POST', '/domains/' . rawurlencode($domain) . '/records', [
            'host'   => $host,
            'type'   => $type,
            'answer' => $answer,
            'ttl'    => (int) $ttl,
        ]);
        return $this->last_status === 200;
    }
}
