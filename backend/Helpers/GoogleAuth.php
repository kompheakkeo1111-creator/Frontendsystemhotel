<?php
namespace App\Helpers;

/**
 * GoogleAuth - Verifies Google Identity Services ID tokens (JWT)
 *
 * Flow: the browser gets an ID token from Google (GIS button), POSTs it to
 * login/google, and this class verifies it server-side:
 *   1. Fetch Google's public signing keys (JWKS) - cached on disk for 24h
 *   2. Verify the RSA-SHA256 signature with openssl_verify()
 *   3. Validate issuer (accounts.google.com), audience (our Client ID),
 *      expiry, and email_verified
 *
 * No composer/package manager is used in this project, so verification is
 * implemented directly on top of PHP's openssl extension.
 */
class GoogleAuth
{
    /** Google's public key endpoint (RSA keys, rotates periodically) */
    const CERTS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    /** Disk cache for the JWKS keys (avoid one HTTP call per login) */
    const CACHE_FILE = __DIR__ . '/../cache/google_jwks.json';

    /** Cache lifetime in seconds */
    const CACHE_TTL = 86400; // 24 hours

    /**
     * Verify a Google ID token and return its payload, or null if invalid.
     *
     * @param string $idToken raw JWT from Google Identity Services
     * @return array|null payload on success (sub, email, name, ...), null on failure
     */
    public static function verifyIdToken($idToken)
    {
        if (!is_string($idToken) || substr_count($idToken, '.') !== 2) {
            return null; // Not a JWT
        }
        if (!function_exists('openssl_verify')) {
            return self::verifyViaTokenInfo($idToken); // Fallback: openssl missing
        }

        $parts = explode('.', $idToken);
        list($headerB64, $payloadB64, $signatureB64) = $parts;

        $header  = json_decode(self::base64UrlDecode($headerB64), true);
        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        // Validate claims before trusting the signature result
        if (!self::validateClaims($payload)) {
            return null;
        }

        // Locate the signing key used for this token
        $jwks = self::getJwks();
        $key  = null;
        if (isset($header['kid'])) {
            foreach ($jwks as $jwk) {
                if (($jwk['kid'] ?? '') === $header['kid'] && ($jwk['kty'] ?? '') === 'RSA') {
                    $key = $jwk;
                    break;
                }
            }
        }
        if ($key === null) {
            return null; // Unknown key (token tampered, or keys rotated)
        }

        $publicKey = self::rsaJwkToPem($key);
        if ($publicKey === null) {
            return null;
        }

        $signature = self::base64UrlDecode($signatureB64);
        $signedInput = $headerB64 . '.' . $payloadB64;

        // Security: signature MUST verify or the token is forged
        if (openssl_verify($signedInput, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }

        return $payload;
    }

    /**
     * Validate standard ID token claims (issuer, audience, expiry, email)
     *
     * @param array $payload decoded JWT payload
     * @return bool
     */
    private static function validateClaims($payload)
    {
        $clientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
        if ($clientId === '' || strpos($clientId, 'YOUR_') === 0) {
            return false; // Google login not configured yet
        }

        // Issuer must be Google
        $iss = $payload['iss'] ?? '';
        if ($iss !== 'https://accounts.google.com' && $iss !== 'accounts.google.com') {
            return false;
        }

        // Audience must be our Client ID
        if (($payload['aud'] ?? '') !== $clientId) {
            return false;
        }

        // Token must not be expired
        if (empty($payload['exp']) || (int)$payload['exp'] < time()) {
            return false;
        }

        // Email must be verified by Google
        if (empty($payload['email']) || empty($payload['email_verified'])) {
            return false;
        }

        return true;
    }

    /**
     * Fetch Google's public keys, using the disk cache when fresh.
     *
     * @return array decoded JWKS "keys" list (empty array on failure)
     */
    private static function getJwks()
    {
        $cacheFile = self::CACHE_FILE;

        // Use cached keys if still valid
        if (is_readable($cacheFile)) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['expires'], $cached['keys'])
                && $cached['expires'] > time()) {
                return $cached['keys'];
            }
        }

        $json = self::httpGet(self::CERTS_URL);
        if ($json === null) {
            // Network failed: fall back to a stale cache if we have one
            $cached = is_readable($cacheFile)
                ? json_decode((string)file_get_contents($cacheFile), true) : null;
            return (is_array($cached) && isset($cached['keys'])) ? $cached['keys'] : [];
        }

        $decoded = json_decode($json, true);
        $keys = is_array($decoded) && isset($decoded['keys']) ? $decoded['keys'] : [];

        // Cache for next time (ignore failures on read-only hosts)
        @file_put_contents($cacheFile, json_encode([
            'expires' => time() + self::CACHE_TTL,
            'keys'    => $keys,
        ]));

        return $keys;
    }

    /**
     * Convert an RSA JWK (n/e) into a PEM public key for openssl_verify()
     *
     * @param array $jwk JSON Web Key with base64url 'n' and 'e'
     * @return string|null PEM encoded key
     */
    private static function rsaJwkToPem($jwk)
    {
        $modulus = self::base64UrlDecode($jwk['n'] ?? '');
        $exponent = self::base64UrlDecode($jwk['e'] ?? '');
        if ($modulus === '' || $exponent === '') {
            return null;
        }

        // DER integers must not have a leading zero unless needed for the sign bit
        $modulus = ltrim($modulus, "\x00");
        $exponent = ltrim($exponent, "\x00");
        if ($modulus === '' || $exponent === '') {
            return null;
        }
        if ((ord($modulus[0]) & 0x80) !== 0) $modulus = "\x00" . $modulus;
        if ((ord($exponent[0]) & 0x80) !== 0) $exponent = "\x00" . $exponent;

        $rsaKeySeq = self::derSequence(
            self::derInteger($modulus) . self::derInteger($exponent)
        );
        // OID 1.2.840.113549.1.1.1 (rsaEncryption) + NULL params
        $algSeq = self::derSequence("\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00");
        $spki   = self::derSequence($algSeq . self::derBitString($rsaKeySeq));

        return "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($spki), 64, "\n")
             . "-----END PUBLIC KEY-----";
    }

    private static function derInteger($bytes)
    {
        return "\x02" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derSequence($bytes)
    {
        return "\x30" . self::derLength(strlen($bytes)) . $bytes;
    }

    private static function derBitString($bytes)
    {
        return "\x03" . self::derLength(strlen($bytes) + 1) . "\x00" . $bytes;
    }

    private static function derLength($length)
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    /**
     * Fallback verification when the openssl extension is unavailable.
     * Asks Google to validate the token directly.
     *
     * @param string $idToken
     * @return array|null
     */
    private static function verifyViaTokenInfo($idToken)
    {
        $json = self::httpGet('https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken));
        if ($json === null) {
            return null;
        }
        $payload = json_decode($json, true);
        if (!is_array($payload)) {
            return null;
        }
        return self::validateClaims($payload) ? $payload : null;
    }

    /**
     * Simple HTTP GET helper.
     * Tries curl first, then falls back to file_get_contents (some WAMP/Windows
     * PHP builds have curl configured without a CA bundle, while OpenSSL has one).
     *
     * @param string $url
     * @return string|null body on success, null on failure
     */
    private static function httpGet($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body !== false && $code === 200) {
                return $body;
            }
            // Curl failed (often SSL CA bundle missing on Windows) - fall through
        }

        $context = stream_context_create([
            'http' => ['timeout' => 5, 'ignore_errors' => true],
        ]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            return null;
        }
        // Confirm a 200 when status info is available
        $status = $http_response_code ?? 0;
        return ($status === 0 || $status === 200) ? $body : null;
    }

    /**
     * RFC 4648 base64url decode
     *
     * @param string $data
     * @return string
     */
    private static function base64UrlDecode($data)
    {
        return (string)base64_decode(strtr($data, '-_', '+/'), true);
    }
}
