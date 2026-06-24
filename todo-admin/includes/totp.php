<?php
/**
 * Lightweight TOTP (RFC 6238) implementation.
 * No external dependencies required.
 */

class TOTP {
    private const DIGITS = 6;
    private const PERIOD = 30;
    private const ALGORITHM = 'sha1';
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 20): string {
        $secret = '';
        $bytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[ord($bytes[$i]) % 32];
        }
        return $secret;
    }

    public static function getCode(string $secret, ?int $timestamp = null): string {
        $timestamp = $timestamp ?? time();
        $timeSlice = intdiv($timestamp, self::PERIOD);
        $timeBytes = pack('N*', 0, $timeSlice);

        $key = self::base32Decode($secret);
        $hash = hash_hmac(self::ALGORITHM, $timeBytes, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, self::DIGITS);

        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code, int $window = 1): bool {
        $now = time();
        for ($i = -$window; $i <= $window; $i++) {
            $ts = $now + ($i * self::PERIOD);
            if (hash_equals(self::getCode($secret, $ts), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function getProvisioningUri(string $secret, string $username, string $issuer = 'AdminTodo'): string {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($username),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    private static function base32Decode(string $input): string {
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos(self::BASE32_CHARS, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}
