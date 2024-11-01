<?php

if (!defined('ABSPATH')) {
  exit();
} // Exit if accessed directly

class WebtonativeJWT
{
  private static $timestamp = null;
  private static $leeway = 0;
  private static $supported_algs = array(
    'ES384' => array('openssl', 'SHA384'),
    'ES256' => array('openssl', 'SHA256'),
    'HS256' => array('hash_hmac', 'SHA256'),
    'HS384' => array('hash_hmac', 'SHA384'),
    'HS512' => array('hash_hmac', 'SHA512'),
    'RS256' => array('openssl', 'SHA256'),
    'RS384' => array('openssl', 'SHA384'),
    'RS512' => array('openssl', 'SHA512'),
    'EdDSA' => array('sodium_crypto', 'EdDSA'),
  );

  public static function parseKeySet(array $jwks)
  {
    $keys = array();
    if (!isset($jwks['keys'])) {
      throw new UnexpectedValueException('"keys" member must exist in the JWK Set');
    }
    if (empty($jwks['keys'])) {
      throw new InvalidArgumentException('JWK Set did not contain any keys');
    }

    foreach ($jwks['keys'] as $k => $v) {
      $kid = isset($v['kid']) ? $v['kid'] : $k;
      if ($key = self::parseKey($v)) {
        $keys[$kid] = $key;
      }
    }

    if (0 === \count($keys)) {
      throw new UnexpectedValueException('No supported algorithms found in JWK Set');
    }

    return $keys;
  }

  private static function parseKey(array $jwk)
  {
    if (empty($jwk)) {
      throw new InvalidArgumentException('JWK must not be empty');
    }
    if (!isset($jwk['kty'])) {
      throw new UnexpectedValueException('JWK must contain a "kty" parameter');
    }
    switch ($jwk['kty']) {
      case 'RSA':
        if (!empty($jwk['d'])) {
          throw new UnexpectedValueException('RSA private keys are not supported');
        }
        if (!isset($jwk['n']) || !isset($jwk['e'])) {
          throw new UnexpectedValueException('RSA keys must contain values for both "n" and "e"');
        }

        $pem = self::createPemFromModulusAndExponent($jwk['n'], $jwk['e']);
        $publicKey = \openssl_pkey_get_public($pem);
        if (false === $publicKey) {
          throw new DomainException('OpenSSL error: ' . \openssl_error_string());
        }
        return $publicKey;
      default:
        // Currently only RSA is supported
        break;
    }
  }

  public static function urlsafeB64Decode($input)
  {
    $remainder = \strlen($input) % 4;
    if ($remainder) {
      $padlen = 4 - $remainder;
      $input .= \str_repeat('=', $padlen);
    }
    return \base64_decode(\strtr($input, '-_', '+/'));
  }

  private static function encodeLength($length)
  {
    if ($length <= 0x7f) {
      return \chr($length);
    }

    $temp = \ltrim(\pack('N', $length), \chr(0));

    return \pack('Ca*', 0x80 | \strlen($temp), $temp);
  }

  private static function createPemFromModulusAndExponent($n, $e)
  {
    $modulus = self::urlsafeB64Decode($n);
    $publicExponent = self::urlsafeB64Decode($e);

    $components = array(
      'modulus' => \pack('Ca*a*', 2, self::encodeLength(\strlen($modulus)), $modulus),
      'publicExponent' => \pack('Ca*a*', 2, self::encodeLength(\strlen($publicExponent)), $publicExponent),
    );

    $rsaPublicKey = \pack(
      'Ca*a*a*',
      48,
      self::encodeLength(\strlen($components['modulus']) + \strlen($components['publicExponent'])),
      $components['modulus'],
      $components['publicExponent']
    );

    // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
    $rsaOID = \pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
    $rsaPublicKey = \chr(0) . $rsaPublicKey;
    $rsaPublicKey = \chr(3) . self::encodeLength(\strlen($rsaPublicKey)) . $rsaPublicKey;

    $rsaPublicKey = \pack('Ca*a*', 48, self::encodeLength(\strlen($rsaOID . $rsaPublicKey)), $rsaOID . $rsaPublicKey);

    $rsaPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" . \chunk_split(\base64_encode($rsaPublicKey), 64) . '-----END PUBLIC KEY-----';

    return $rsaPublicKey;
  }

  public static function jsonDecode($input)
  {
    if (\version_compare(PHP_VERSION, '5.4.0', '>=') && !(\defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
      /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
       * to specify that large ints (like Steam Transaction IDs) should be treated as
       * strings, rather than the PHP default behaviour of converting them to floats.
       */
      $obj = \json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
    } else {
      /** Not all servers will support that, however, so for older versions we must
       * manually detect large ints in the JSON string and quote them (thus converting
       *them to strings) before decoding, hence the preg_replace() call.
       */
      $max_int_length = \strlen((string) PHP_INT_MAX) - 1;
      $json_without_bigints = \preg_replace('/:\s*(-?\d{' . $max_int_length . ',})/', ': "$1"', $input);
      $obj = \json_decode($json_without_bigints);
    }

    if ($errno = \json_last_error()) {
      static::handleJsonError($errno);
    } elseif ($obj === null && $input !== 'null') {
      throw new DomainException('Null result with non-null input');
    }
    return $obj;
  }

  private static function getKeyMaterialAndAlgorithm($keyOrKeyArray, $kid = null)
  {
    if (is_string($keyOrKeyArray) || is_resource($keyOrKeyArray) || $keyOrKeyArray instanceof OpenSSLAsymmetricKey) {
      return array($keyOrKeyArray, null);
    }

    if ($keyOrKeyArray instanceof Key) {
      return array($keyOrKeyArray->getKeyMaterial(), $keyOrKeyArray->getAlgorithm());
    }

    if (is_array($keyOrKeyArray) || $keyOrKeyArray instanceof ArrayAccess) {
      if (!isset($kid)) {
        throw new UnexpectedValueException('"kid" empty, unable to lookup correct key');
      }
      if (!isset($keyOrKeyArray[$kid])) {
        throw new UnexpectedValueException('"kid" invalid, unable to lookup correct key');
      }

      $key = $keyOrKeyArray[$kid];

      if ($key instanceof Key) {
        return array($key->getKeyMaterial(), $key->getAlgorithm());
      }

      return array($key, null);
    }

    throw new UnexpectedValueException(
      '$keyOrKeyArray must be a string|resource key, an array of string|resource keys, ' . 'an instance of Firebase\JWT\Key key or an array of Firebase\JWT\Key keys'
    );
  }

  private static function verify($msg, $signature, $key, $alg)
  {
    if (empty(static::$supported_algs[$alg])) {
      throw new DomainException('Algorithm not supported');
    }

    list($function, $algorithm) = static::$supported_algs[$alg];
    switch ($function) {
      case 'openssl':
        $success = \openssl_verify($msg, $signature, $key, $algorithm);
        if ($success === 1) {
          return true;
        } elseif ($success === 0) {
          return false;
        }
        // returns 1 on success, 0 on failure, -1 on error.
        throw new DomainException('OpenSSL error: ' . \openssl_error_string());
      case 'sodium_crypto':
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
          throw new DomainException('libsodium is not available');
        }
        try {
          // The last non-empty line is used as the key.
          $lines = array_filter(explode("\n", $key));
          $key = base64_decode(end($lines));
          return sodium_crypto_sign_verify_detached($signature, $msg, $key);
        } catch (Exception $e) {
          throw new DomainException($e->getMessage(), 0, $e);
        }
      case 'hash_hmac':
      default:
        $hash = \hash_hmac($algorithm, $msg, $key, true);
        return self::constantTimeEquals($signature, $hash);
    }
  }

  public static function decode($jwt, $keyOrKeyArray, array $allowed_algs = array())
  {
    $timestamp = \is_null(static::$timestamp) ? \time() : static::$timestamp;

    if (empty($keyOrKeyArray)) {
      throw new InvalidArgumentException('Key may not be empty');
    }
    $tks = \explode('.', $jwt);
    if (\count($tks) != 3) {
      throw new UnexpectedValueException('Wrong number of segments');
    }
    list($headb64, $bodyb64, $cryptob64) = $tks;
    if (null === ($header = static::jsonDecode(static::urlsafeB64Decode($headb64)))) {
      throw new UnexpectedValueException('Invalid header encoding');
    }
    if (null === ($payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64)))) {
      throw new UnexpectedValueException('Invalid claims encoding');
    }
    if (false === ($sig = static::urlsafeB64Decode($cryptob64))) {
      throw new UnexpectedValueException('Invalid signature encoding');
    }
    if (empty($header->alg)) {
      throw new UnexpectedValueException('Empty algorithm');
    }
    if (empty(static::$supported_algs[$header->alg])) {
      throw new UnexpectedValueException('Algorithm not supported');
    }

    list($keyMaterial, $algorithm) = self::getKeyMaterialAndAlgorithm($keyOrKeyArray, empty($header->kid) ? null : $header->kid);

    if (empty($algorithm)) {
      // Use deprecated "allowed_algs" to determine if the algorithm is supported.
      // This opens up the possibility of an attack in some implementations.
      // @see https://github.com/firebase/php-jwt/issues/351
      if (!\in_array($header->alg, $allowed_algs)) {
        throw new UnexpectedValueException('Algorithm not allowed');
      }
    } else {
      // Check the algorithm
      if (!self::constantTimeEquals($algorithm, $header->alg)) {
        // See issue #351
        throw new UnexpectedValueException('Incorrect key for this algorithm');
      }
    }
    if ($header->alg === 'ES256' || $header->alg === 'ES384') {
      // OpenSSL expects an ASN.1 DER sequence for ES256/ES384 signatures
      $sig = self::signatureToDER($sig);
    }

    if (!static::verify("$headb64.$bodyb64", $sig, $keyMaterial, $header->alg)) {
      throw new SignatureInvalidException('Signature verification failed');
    }

    // Check the nbf if it is defined. This is the time that the
    // token can actually be used. If it's not yet that time, abort.
    if (isset($payload->nbf) && $payload->nbf > $timestamp + static::$leeway) {
      throw new BeforeValidException('Cannot handle token prior to ' . \date(DateTime::ISO8601, $payload->nbf));
    }

    // Check that this token has been created before 'now'. This prevents
    // using tokens that have been created for later use (and haven't
    // correctly used the nbf claim).
    if (isset($payload->iat) && $payload->iat > $timestamp + static::$leeway) {
      throw new BeforeValidException('Cannot handle token prior to ' . \date(DateTime::ISO8601, $payload->iat));
    }

    // Check if this token has expired.
    if (isset($payload->exp) && $timestamp - static::$leeway >= $payload->exp) {
      throw new ExpiredException('Expired token');
    }

    return $payload;
  }
}

?>
