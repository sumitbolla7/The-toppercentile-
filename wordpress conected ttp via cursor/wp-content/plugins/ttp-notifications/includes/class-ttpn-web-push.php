<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimal Web Push sender (VAPID + aes128gcm).
 */
class TTPN_Web_Push {

    private $public_key;
    private $private_key;

    public function __construct($public_key, $private_key) {
        $this->public_key  = $public_key;
        $this->private_key = $private_key;
    }

    public function send($endpoint, $p256dh, $auth, $payload) {
        if (!function_exists('openssl_pkey_new')) {
            return new WP_Error('openssl_missing', 'OpenSSL is required for push notifications.');
        }

        $user_public  = $this->base64url_decode($p256dh);
        $user_auth    = $this->base64url_decode($auth);
        $server_keys  = $this->generate_server_keys();
        $shared_secret = $this->get_shared_secret($server_keys['private'], $user_public);
        $salt         = random_bytes(16);
        $cek_info     = "Content-Encoding: aes128gcm\x00";
        $nonce_info   = "Content-Encoding: nonce\x00";
        $prk          = hash_hmac('sha256', $shared_secret, $user_auth, true);
        $cek          = $this->hkdf($salt, $prk, $cek_info, 16);
        $nonce        = $this->hkdf($salt, $prk, $nonce_info, 12);
        $encrypted    = $this->encrypt_payload($payload, $cek, $nonce);

        $body = $salt . pack('N', 4096) . pack('C', strlen($server_keys['public'])) . $server_keys['public'] . $encrypted;

        $jwt = $this->create_vapid_jwt($endpoint);

        $response = wp_remote_post($endpoint, [
            'timeout' => 15,
            'headers' => [
                'Content-Type'     => 'application/octet-stream',
                'Content-Encoding' => 'aes128gcm',
                'TTL'              => '86400',
                'Authorization'    => 'vapid t=' . $jwt . ', k=' . $this->public_key,
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if (in_array($code, [404, 410], true)) {
            return new WP_Error('gone', 'Subscription expired.', ['status' => $code]);
        }

        if ($code < 200 || $code >= 300) {
            return new WP_Error('push_failed', 'Push delivery failed.', ['status' => $code]);
        }

        return true;
    }

    private function generate_server_keys() {
        $key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $details = openssl_pkey_get_details($key);

        return [
            'private' => $details['ec']['d'],
            'public'  => "\x04" . $details['ec']['x'] . $details['ec']['y'],
        ];
    }

    private function get_shared_secret($private_key, $public_key) {
        $private_pem = $this->private_key_to_pem($private_key);
        $peer        = $this->uncompressed_to_pem($public_key);

        if (!$private_pem || !$peer) {
            return '';
        }

        if (function_exists('openssl_pkey_derive')) {
            $shared = openssl_pkey_derive($peer, $private_pem);

            return is_string($shared) ? $shared : '';
        }

        return '';
    }

    private function uncompressed_to_pem($uncompressed) {
        $der = hex2bin(
            '3059301306072a8648ce3d020106082a8648ce3d030107034200' . bin2hex($uncompressed)
        );
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";

        return openssl_pkey_get_public($pem);
    }

    private function encrypt_payload($payload, $cek, $nonce) {
        $padding = "\x02" . str_repeat("\0", 0);
        $plaintext = $padding . $payload;

        if (PHP_VERSION_ID >= 70100 && in_array('aes-128-gcm', openssl_get_cipher_methods(), true)) {
            $tag = '';
            $cipher = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
            return $cipher . $tag;
        }

        return $plaintext;
    }

    private function hkdf($salt, $ikm, $info, $length) {
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $t   = '';
        $okm = '';
        $i   = 0;

        while (strlen($okm) < $length) {
            $i++;
            $t    = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
            $okm .= $t;
        }

        return substr($okm, 0, $length);
    }

    private function create_vapid_jwt($endpoint) {
        $aud = wp_parse_url($endpoint, PHP_URL_SCHEME) . '://' . wp_parse_url($endpoint, PHP_URL_HOST);
        $header = $this->base64url_encode(wp_json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = $this->base64url_encode(wp_json_encode([
            'aud' => $aud,
            'exp' => time() + 43200,
            'sub' => 'mailto:' . get_option('admin_email'),
        ]));
        $data = $header . '.' . $claims;

        $private_pem = $this->private_key_to_pem($this->base64url_decode($this->private_key));
        openssl_sign($data, $signature, $private_pem, OPENSSL_ALGO_SHA256);

        return $data . '.' . $this->base64url_encode($this->der_to_raw($signature));
    }

    private function private_key_to_pem($private) {
        $public_key = openssl_pkey_new([
            'curve_name'       => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
        $details = openssl_pkey_get_details($public_key);
        $details['ec']['d'] = $private;

        $der = $this->ec_private_key_der($details['ec']);
        $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";

        return openssl_pkey_get_private($pem);
    }

    private function ec_private_key_der($ec) {
        $version = "\x02\x01\x01";
        $private = "\x04\x20" . $ec['d'];
        $oid     = hex2bin('06072a8648ce3d020106082a8648ce3d030107');
        $pub     = "\x03\x42\x00\x04" . $ec['x'] . $ec['y'];
        $seq     = "\x30\x77" . $version . "\x30\x10" . $oid . "\x04\x20" . $ec['d'] . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07" . "\xa1\x44\x03\x42\x00\x04" . $ec['x'] . $ec['y'];

        return $seq;
    }

    private function der_to_raw($der) {
        $offset = 3;
        $r_len  = ord($der[$offset + 1]);
        $r      = substr($der, $offset + 2, $r_len);
        $offset = $offset + 2 + $r_len + 1;
        $s_len  = ord($der[$offset + 1]);
        $s      = substr($der, $offset + 2, $s_len);

        return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT) . str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
