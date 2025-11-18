<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Firebase\JWT\Key;

/**
 * Okta JWT Verifier Library
 * 
 * Handles JWT token validation and verification for Okta authentication
 */
class Okta_jwt_verifier {
    
    protected $CI;
    protected $okta_domain;
    protected $client_id;
    protected $auth_server_id;
    protected $issuer;
    protected $leeway;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->config->load('okta');
        
        $this->okta_domain = $this->CI->config->item('okta_domain');
        $this->client_id = $this->CI->config->item('okta_client_id');
        $this->auth_server_id = $this->CI->config->item('okta_auth_server_id');
        $this->leeway = $this->CI->config->item('okta_leeway') ?: 120;
        
        // Set the issuer URL
        $this->issuer = "https://{$this->okta_domain}/oauth2/{$this->auth_server_id}";
    }
    
    /**
     * Verify JWT token
     * 
     * @param string $token The JWT token to verify
     * @return object|false Returns decoded token payload or false on failure
     */
    public function verify($token) {
        try {
            // Get the JWKS (JSON Web Key Set) from Okta
            $jwks = $this->get_jwks();
            
            if (!$jwks) {
                log_message('error', 'Failed to retrieve JWKS from Okta');
                return FALSE;
            }
            
            // Set leeway for time-based claims
            JWT::$leeway = $this->leeway;
            
            // Decode the token header to get the key ID (kid)
            $tks = explode('.', $token);
            if (count($tks) != 3) {
                log_message('error', 'Invalid JWT token format');
                return FALSE;
            }
            
            $headb64 = $tks[0];
            $header = json_decode(JWT::urlsafeB64Decode($headb64));
            
            if (!isset($header->kid)) {
                log_message('error', 'JWT token missing kid in header');
                return FALSE;
            }
            
            // Find the matching key from JWKS
            $key = $this->find_key($jwks, $header->kid);
            
            if (!$key) {
                log_message('error', 'No matching key found in JWKS');
                return FALSE;
            }
            
            // Verify and decode the token
            $decoded = JWT::decode($token, $key);
            
            // Validate claims
            if (!$this->validate_claims($decoded)) {
                log_message('error', 'JWT token claims validation failed');
                return FALSE;
            }
            
            return $decoded;
            
        } catch (Exception $e) {
            log_message('error', 'JWT verification failed: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Get JSON Web Key Set from Okta
     * 
     * @return array|false
     */
    protected function get_jwks() {
        $jwks_uri = $this->issuer . '/v1/keys';
        
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $jwks_uri, [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);
            
            if ($response->getStatusCode() === 200) {
                $body = json_decode($response->getBody()->getContents(), true);
                return $body['keys'] ?? FALSE;
            }
            
            return FALSE;
            
        } catch (Exception $e) {
            log_message('error', 'Failed to fetch JWKS: ' . $e->getMessage());
            return FALSE;
        }
    }
    
    /**
     * Find the matching key from JWKS
     * 
     * @param array $keys The keys from JWKS
     * @param string $kid The key ID to find
     * @return Key|false
     */
    protected function find_key($keys, $kid) {
        foreach ($keys as $key) {
            if ($key['kid'] === $kid) {
                // Convert JWK to PEM format
                return new Key(
                    $this->jwk_to_pem($key),
                    $key['alg'] ?? 'RS256'
                );
            }
        }
        return FALSE;
    }
    
    /**
     * Convert JWK to PEM format
     * 
     * @param array $jwk
     * @return string
     */
    protected function jwk_to_pem($jwk) {
        if ($jwk['kty'] !== 'RSA') {
            throw new Exception('Only RSA keys are supported');
        }
        
        $n = $this->base64_url_decode($jwk['n']);
        $e = $this->base64_url_decode($jwk['e']);
        
        $modulus = unpack('C*', $n);
        $publicExponent = unpack('C*', $e);
        
        $components = array(
            'modulus' => $modulus,
            'publicExponent' => $publicExponent
        );
        
        $rsaPublicKey = $this->encode_asn1($components);
        
        $rsaOID = pack('H*', '300d06092a864886f70d0101010500');
        $rsaPublicKey = chr(0) . $rsaPublicKey;
        $rsaPublicKey = $this->encode_length(strlen($rsaPublicKey)) . $rsaPublicKey;
        $rsaPublicKey = "\x03" . $rsaPublicKey;
        $rsaPublicKey = $rsaOID . $rsaPublicKey;
        $rsaPublicKey = $this->encode_length(strlen($rsaPublicKey)) . $rsaPublicKey;
        $rsaPublicKey = "\x30" . $rsaPublicKey;
        
        return "-----BEGIN PUBLIC KEY-----\n" .
               chunk_split(base64_encode($rsaPublicKey), 64) .
               "-----END PUBLIC KEY-----";
    }
    
    /**
     * Encode ASN.1 structure
     */
    protected function encode_asn1($components) {
        $modulus = $this->encode_integer($components['modulus']);
        $exponent = $this->encode_integer($components['publicExponent']);
        
        $sequence = $modulus . $exponent;
        $sequence = $this->encode_length(strlen($sequence)) . $sequence;
        
        return "\x30" . $sequence;
    }
    
    /**
     * Encode integer for ASN.1
     */
    protected function encode_integer($data) {
        $data = array_values($data);
        
        // Add padding if needed
        if ($data[0] > 0x7f) {
            array_unshift($data, 0);
        }
        
        $result = "\x02" . $this->encode_length(count($data));
        foreach ($data as $byte) {
            $result .= chr($byte);
        }
        
        return $result;
    }
    
    /**
     * Encode length for ASN.1
     */
    protected function encode_length($length) {
        if ($length <= 0x7f) {
            return chr($length);
        }
        
        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }
    
    /**
     * Base64 URL decode
     */
    protected function base64_url_decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
    
    /**
     * Validate JWT claims
     * 
     * @param object $decoded
     * @return bool
     */
    protected function validate_claims($decoded) {
        // Validate issuer
        if (!isset($decoded->iss) || $decoded->iss !== $this->issuer) {
            log_message('error', 'Invalid issuer in JWT token');
            return FALSE;
        }
        
        // Validate audience (client ID)
        if (!isset($decoded->aud) || $decoded->aud !== $this->client_id) {
            log_message('error', 'Invalid audience in JWT token');
            return FALSE;
        }
        
        // Validate expiration
        if (!isset($decoded->exp) || $decoded->exp < time()) {
            log_message('error', 'JWT token has expired');
            return FALSE;
        }
        
        // Validate issued at time
        if (!isset($decoded->iat) || $decoded->iat > time() + $this->leeway) {
            log_message('error', 'Invalid issued at time in JWT token');
            return FALSE;
        }
        
        return TRUE;
    }
}
