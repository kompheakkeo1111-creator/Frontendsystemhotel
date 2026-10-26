<?php
/**
 * Tiny HTTP client for integration/system tests (uses cURL).
 * Returns: ['status' => int, 'body' => string, 'headers' => array, 'cookie' => string]
 */
class Http
{
    public static function get($url, $opts = [])
    {
        return self::request('GET', $url, $opts);
    }

    /**
     * POST data. $data: array (form-encoded unless $opts['json'] = true) or raw string.
     */
    public static function post($url, $data, $opts = [])
    {
        $opts['post'] = $data;
        return self::request('POST', $url, $opts);
    }

    public static function request($method, $url, $opts = [])
    {
        $ch = curl_init($url);
        $headers = isset($opts['headers']) ? $opts['headers'] : [];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => !empty($opts['follow']),
            CURLOPT_MAXREDIRS      => 5,
        ]);

        if (!empty($opts['cookie'])) {
            $headers[] = 'Cookie: ' . $opts['cookie'];
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            $post = $opts['post'];
            if (is_array($post)) {
                if (!empty($opts['json'])) {
                    $post = json_encode($post);
                    $headers[] = 'Content-Type: application/json';
                } else {
                    $post = http_build_query($post);
                    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                }
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('HTTP request failed for ' . $url . ': ' . $err);
        }

        $status     = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerStr = substr($raw, 0, $headerSize);
        $body      = substr($raw, $headerSize);

        // Split headers into blocks (redirects produce several); keep the LAST block.
        $blocks = preg_split("/\r?\n\r?\n/", trim($headerStr));
        $lastBlock = end($blocks);

        $map = [];
        $cookies = [];
        foreach (preg_split("/\r?\n/", $lastBlock) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $line, 2);
            $name  = strtolower(trim($name));
            $value = trim($value);
            $map[$name] = $value; // last occurrence wins (Location, Set-Cookie, ...)
            if ($name === 'set-cookie') {
                $cookies[] = $value;
            }
        }

        return [
            'status'  => $status,
            'body'    => $body,
            'headers' => $map,
            'cookies' => $cookies,
        ];
    }

    /** Convenience: extract PHPSESSID from a Set-Cookie header list. */
    public static function sessionCookie(array $response)
    {
        foreach ($response['cookies'] as $c) {
            if (preg_match('/PHPSESSID=([^;]+)/', $c, $m)) {
                return 'PHPSESSID=' . $m[1];
            }
        }
        return '';
    }
}
