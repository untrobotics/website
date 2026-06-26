<?php

require(__DIR__ . "/vendor/autoload.php");

class Interaction {
    public static function verifyKey($rawBody, $signature, $timestamp, $client_public_key) {
        $ec = new \Elliptic\EdDSA('ed25519');
        $key = $ec->keyFromPublic($client_public_key);

        $message = array_merge(unpack('C*', $timestamp), unpack('C*', $rawBody));
        return $key->verify($message, $signature) == TRUE;
    }
}