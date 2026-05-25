<?php
// lib/PingValidator.php — Task 5.1
class PingValidator {
    public static function validate($data) {
        if (!TokenService::validateFormat($data['token'] ?? '')) {
            return ['valid' => false, 'reason' => 'invalid token format'];
        }
        if (!in_array($data['milestone_type'] ?? '', ['pickup','transit','delivery'])) {
            return ['valid' => false, 'reason' => 'invalid milestone'];
        }
        if (!is_numeric($data['stop_sequence'] ?? 0) || $data['stop_sequence'] < 1) {
            return ['valid' => false, 'reason' => 'invalid stop_sequence'];
        }
        return ['valid' => true];
    }
}
?>
