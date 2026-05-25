<?php
// lib/LocationService.php — Task 5.1 + 5.5
class LocationService {
    public static function shouldGeocode($milestone) {
        return $milestone === 'delivery'; // selective — only final delivery
    }

    public static function geocode($lat, $lng) {
        // Nominatim call with User-Agent compliance (rate limit enforced by caller)
        $url = "https://nominatim.openstreetmap.org/reverse?lat=$lat&lon=$lng&format=json";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Exspeedite-TMS/1.0 tracking@exspeeditecheckpoint.com');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['display_name'] ?? null;
    }
}
?>
