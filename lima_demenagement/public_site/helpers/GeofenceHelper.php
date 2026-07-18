<?php
// LIMA Solutions ERP - Geofencing Helper

class GeofenceHelper {

    /**
     * Extracts a Swiss 4-digit zip code (NPA) from a string.
     */
    public static function extractSwissNpa($address) {
        if (empty($address)) return null;
        if (preg_match('/\b(\d{4})\b/', $address, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    /**
     * Lightweight database/mapping of Swiss NPA prefixes to coordinates (Lat, Lng)
     * as a deterministic fallback proxy for distance.
     */
    public static function getCoordinatesForNpa($npa) {
        $npa = (int)$npa;
        if ($npa < 1000 || $npa > 9999) return null;

        $prefix2 = (int)substr(str_pad($npa, 4, '0', STR_PAD_LEFT), 0, 2);

        switch ($prefix2) {
            case 10: // Lausanne
                return ['latitude' => 46.5197, 'longitude' => 6.6323];
            case 11: // Morges
                return ['latitude' => 46.5088, 'longitude' => 6.4993];
            case 12: // Geneva
                return ['latitude' => 46.2044, 'longitude' => 6.1432];
            case 13: // Yverdon
                return ['latitude' => 46.7785, 'longitude' => 6.6412];
            case 14: // Estavayer
                return ['latitude' => 46.8488, 'longitude' => 6.8481];
            case 15: // Payerne
                return ['latitude' => 46.8209, 'longitude' => 6.9377];
            case 16: // Bulle
                return ['latitude' => 46.6186, 'longitude' => 7.0583];
            case 17: // Fribourg
                return ['latitude' => 46.8064, 'longitude' => 7.1619];
            case 18: // Vevey / Montreux / Chablais
                return ['latitude' => 46.4628, 'longitude' => 6.8419];
            case 19: // Sion / Martigny / Sierre (Valais)
                return ['latitude' => 46.2294, 'longitude' => 7.3598];
            case 20: // Neuchâtel
                return ['latitude' => 46.9900, 'longitude' => 6.9293];
            case 30: // Bern
                return ['latitude' => 46.9480, 'longitude' => 7.4474];
            case 40: // Basel
                return ['latitude' => 47.5596, 'longitude' => 7.5886];
            case 69: // Lugano / Ticino
                return ['latitude' => 46.0037, 'longitude' => 8.9511];
            case 80: // Zurich
                return ['latitude' => 47.3769, 'longitude' => 8.5417];
            default:
                $firstDigit = (int)substr(str_pad($npa, 4, '0', STR_PAD_LEFT), 0, 1);
                switch ($firstDigit) {
                    case 1: return ['latitude' => 46.5197, 'longitude' => 6.6323]; // Vaud/Geneve/Valais/Fribourg fallback
                    case 2: return ['latitude' => 46.9900, 'longitude' => 6.9293]; // Neuchâtel/Jura fallback
                    case 3: return ['latitude' => 46.9480, 'longitude' => 7.4474]; // Bern fallback
                    case 4: return ['latitude' => 47.5596, 'longitude' => 7.5886]; // Basel/Solothurn fallback
                    case 5: return ['latitude' => 47.3915, 'longitude' => 8.0513]; // Aargau fallback
                    case 6: return ['latitude' => 46.0037, 'longitude' => 8.9511]; // Ticino / Central fallback
                    case 7: return ['latitude' => 46.8508, 'longitude' => 9.5320]; // Grisons fallback
                    case 8: return ['latitude' => 47.3769, 'longitude' => 8.5417]; // Zurich fallback
                    case 9: return ['latitude' => 47.4239, 'longitude' => 9.3748]; // St. Gallen fallback
                    default: return ['latitude' => 46.5197, 'longitude' => 6.6323];
                }
        }
    }

    /**
     * Calculates the distance in meters between two lat/long coordinates using Haversine.
     */
    public static function calculateHaversine($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371000; // Earth radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Get geofence targets for a project.
     * Extracts coordinates (if exist on project/client) or resolves Swiss NPA coordinate fallbacks.
     */
    public static function getGeofenceTargets($project, $client) {
        $targets = [
            'radius_meters' => 100,
            'origin' => null,
            'destination' => null
        ];

        // 1. Origin Geofence (Carga / Origine)
        // Extract address string from project description or client details
        $originAddress = $project['description'] ?? '';
        $originNpa = self::extractSwissNpa($originAddress);
        
        $originCoords = null;
        if (isset($project['origin_latitude']) && isset($project['origin_longitude']) && 
            $project['origin_latitude'] != 0 && $project['origin_longitude'] != 0) {
            $originCoords = [
                'latitude' => (float)$project['origin_latitude'],
                'longitude' => (float)$project['origin_longitude'],
                'is_approximate' => false
            ];
        } elseif ($originNpa) {
            $approxCoords = self::getCoordinatesForNpa($originNpa);
            if ($approxCoords) {
                $originCoords = [
                    'latitude' => $approxCoords['latitude'],
                    'longitude' => $approxCoords['longitude'],
                    'is_approximate' => true
                ];
            }
        }

        $targets['origin'] = [
            'npa' => $originNpa,
            'coords' => $originCoords
        ];

        // 2. Destination Geofence (Décharge / Destination)
        $destAddress = $client['address'] ?? '';
        $destNpa = self::extractSwissNpa($client['postal_code'] ?? '') ?: self::extractSwissNpa($destAddress);
        
        $destCoords = null;
        if (isset($project['destination_latitude']) && isset($project['destination_longitude']) && 
            $project['destination_latitude'] != 0 && $project['destination_longitude'] != 0) {
            $destCoords = [
                'latitude' => (float)$project['destination_latitude'],
                'longitude' => (float)$project['destination_longitude'],
                'is_approximate' => false
            ];
        } elseif ($destNpa) {
            $approxCoords = self::getCoordinatesForNpa($destNpa);
            if ($approxCoords) {
                $destCoords = [
                    'latitude' => $approxCoords['latitude'],
                    'longitude' => $approxCoords['longitude'],
                    'is_approximate' => true
                ];
            }
        }

        $targets['destination'] = [
            'npa' => $destNpa,
            'coords' => $destCoords
        ];

        return $targets;
    }
}
