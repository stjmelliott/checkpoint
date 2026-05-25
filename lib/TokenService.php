<?php
// lib/TokenService.php — Task 5.1 + 5.3
class TokenService {
    public static function generate() {
        return bin2hex(random_bytes(32));
    }

    public static function validateFormat($token) {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1;
    }

    public static function getExpiryDays() {
        return 7; // constant from spec
    }
}
?>
