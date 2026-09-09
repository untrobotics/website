<?php

require(__DIR__ . "/vendor/autoload.php");

/**
 * Verifies incoming Discord interaction webhook requests.
 */
class Interaction {
    /**
     * Verify the Ed25519 signature Discord attaches to an interaction request.
     *
     * @param string $rawBody           The raw (unparsed) request body.
     * @param string $signature         The hex signature from the X-Signature-Ed25519 header.
     * @param string $timestamp         The X-Signature-Timestamp header value.
     * @param string $client_public_key The application's public key (hex) from the Discord developer portal.
     * @return bool True if the signature is valid for the given body and timestamp.
     */
    public static function verifyKey($rawBody, $signature, $timestamp, $client_public_key) {
        $ec = new \Elliptic\EdDSA('ed25519');
        $key = $ec->keyFromPublic($client_public_key);

        $message = array_merge(unpack('C*', $timestamp), unpack('C*', $rawBody));
        return $key->verify($message, $signature) == TRUE;
    }
}