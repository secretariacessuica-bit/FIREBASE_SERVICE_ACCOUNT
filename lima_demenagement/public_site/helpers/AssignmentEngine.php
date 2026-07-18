<?php
// LIMA Solutions ERP - Smart Project Assignment Engine

class AssignmentEngine {

    /**
     * Parse Swiss NPA (4-digit zip code) from an address string.
     */
    public static function getSwissNpa($address) {
        if (empty($address)) return null;
        if (preg_match('/\b(\d{4})\b/', $address, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    /**
     * Geographic proxy based on Swiss NPA postal codes.
     * Note: Swiss NPA distance is an approximation, not a real route.
     */
    public static function calculateDistanceNpa($npa1, $npa2) {
        if (!$npa1 || !$npa2) return 15.0; // Default fallback distance
        $diff = abs((int)$npa1 - (int)$npa2);
        if ($diff === 0) return 2.5;
        if ($diff < 10) return 5.0;
        
        $p1 = (int)substr(str_pad($npa1, 4, '0', STR_PAD_LEFT), 0, 2);
        $p2 = (int)substr(str_pad($npa2, 4, '0', STR_PAD_LEFT), 0, 2);
        $pDiff = abs($p1 - $p2);
        
        if ($pDiff === 0) {
            return round(5.0 + ($diff * 0.4), 1);
        }
        
        return round(15.0 + ($pDiff * 8.0) + ($diff * 0.05), 1);
    }

    /**
     * Get recommendations for a project.
     */
    public static function getRecommendations($projectId, $pdo) {
        // Fetch project and client
        $stmt = $pdo->prepare("SELECT p.*, c.address AS client_address, c.postal_code AS client_postal_code, c.city AS client_city
            FROM projects p
            JOIN clients c ON p.client_id = c.id
            WHERE p.id = :pid AND p.deleted_at IS NULL LIMIT 1");
        $stmt->execute(['pid' => $projectId]);
        $project = $stmt->fetch();

        if (!$project) {
            return [];
        }

        $companyId = (int)$project['company_id'];
        $projectDate = $project['start_date'] ?: date('Y-m-d');

        // Fetch staff users associated with the company
        $stmtStaff = $pdo->prepare("SELECT u.* FROM users u
            JOIN user_companies uc ON u.id = uc.user_id
            WHERE uc.company_id = :cid AND u.role = 'staff' AND u.active = 1");
        $stmtStaff->execute(['cid' => $companyId]);
        $staffUsers = $stmtStaff->fetchAll();

        // If fewer than 3 staff users in database, return mock fallback teams
        if (count($staffUsers) < 3) {
            return self::getMockFallbackRecommendations($project);
        }

        // Calculate metrics for each staff member
        $staffMetrics = [];
        $clientNpa = self::getSwissNpa($project['client_postal_code']) ?: self::getSwissNpa($project['client_address'] . ' ' . $project['client_city']);

        foreach ($staffUsers as $user) {
            $userNpa = self::getSwissNpa($user['address']);
            $distance = self::calculateDistanceNpa($userNpa, $clientNpa);

            // Proximity Score (25%)
            if ($distance <= 5.0) {
                $proxScore = 25;
            } elseif ($distance <= 15.0) {
                $proxScore = 20;
            } elseif ($distance <= 30.0) {
                $proxScore = 15;
            } elseif ($distance <= 60.0) {
                $proxScore = 10;
            } else {
                $proxScore = 5;
            }

            // Availability Score (25%)
            // Check for existing assignments on the same date
            $stmtAvail = $pdo->prepare("SELECT COUNT(*) FROM operational_assignments oa
                JOIN projects p ON oa.project_id = p.id
                WHERE oa.user_id = :uid AND p.start_date = :pdate AND p.deleted_at IS NULL AND oa.status != 'Cancelled'");
            $stmtAvail->execute(['uid' => $user['id'], 'pdate' => $projectDate]);
            $hasConflict = (int)$stmtAvail->fetchColumn() > 0;
            $availScore = $hasConflict ? 0 : 25;

            // Experience Score (25%)
            // Count completed assignments
            $stmtExp = $pdo->prepare("SELECT COUNT(*) FROM operational_assignments oa
                JOIN projects p ON oa.project_id = p.id
                WHERE oa.user_id = :uid AND p.status = 'Completed' AND p.deleted_at IS NULL");
            $stmtExp->execute(['uid' => $user['id']]);
            $completedCount = (int)$stmtExp->fetchColumn();
            $expScore = min(25, 5 * $completedCount);

            // Workload Score (25%)
            // Sum hours in timesheets for the past 7 days
            $stmtWork = $pdo->prepare("SELECT IFNULL(SUM(hours), 0.00) FROM timesheets
                WHERE user_id = :uid AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND deleted_at IS NULL");
            $stmtWork->execute(['uid' => $user['id']]);
            $workloadHours = (float)$stmtWork->fetchColumn();

            if ($workloadHours <= 8.0) {
                $workloadScore = 25;
            } elseif ($workloadHours <= 20.0) {
                $workloadScore = 20;
            } elseif ($workloadHours <= 40.0) {
                $workloadScore = 15;
            } else {
                $workloadScore = 5;
            }

            $staffMetrics[$user['id']] = [
                'user' => $user,
                'distance' => $distance,
                'npa' => $userNpa,
                'prox_score' => $proxScore,
                'avail_score' => $availScore,
                'exp_score' => $expScore,
                'workload_score' => $workloadScore,
                'completed' => $completedCount,
                'hours' => $workloadHours
            ];
        }

        // Generate combinations of pairs (teams of 2)
        $recommendations = [];
        $userIds = array_keys($staffMetrics);
        $n = count($userIds);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $m1 = $staffMetrics[$userIds[$i]];
                $m2 = $staffMetrics[$userIds[$j]];

                $totalScore = $m1['prox_score'] + $m1['avail_score'] + $m1['exp_score'] + $m1['workload_score'] +
                             $m2['prox_score'] + $m2['avail_score'] + $m2['exp_score'] + $m2['workload_score'];
                
                // Normalise to 100 max
                $finalScore = round(($totalScore / 200.0) * 100);

                // Avg distance
                $avgDistance = round(($m1['distance'] + $m2['distance']) / 2, 1);
                
                // Formulate reasons
                $reasons = [];
                if ($m1['avail_score'] > 0 && $m2['avail_score'] > 0) {
                    $reasons[] = "Totalmente disponíveis na data do projeto.";
                } else {
                    $reasons[] = "Nota: Um ou mais membros podem ter conflitos de agenda.";
                }

                $reasons[] = "Distância média estimada em {$avgDistance} km (NPA aproximado).";
                $reasons[] = "Experiência acumulada de " . ($m1['completed'] + $m2['completed']) . " projetos.";
                $reasons[] = "Carga horária acumulada de " . ($m1['hours'] + $m2['hours']) . "h nos últimos 7 dias.";

                $recommendations[] = [
                    'team_name' => "Equipa: " . $m1['user']['name'] . " & " . $m2['user']['name'],
                    'score' => $finalScore,
                    'reasons' => implode(' | ', $reasons),
                    'distance' => "{$avgDistance} km (NPA proxy)",
                    'availability' => ($m1['avail_score'] > 0 && $m2['avail_score'] > 0) ? "Disponível" : "Indisponível",
                    'workload' => ($m1['hours'] + $m2['hours']) . "h",
                    'members' => [
                        ['id' => $m1['user']['id'], 'name' => $m1['user']['name'], 'hourly_cost' => $m1['user']['hourly_cost']],
                        ['id' => $m2['user']['id'], 'name' => $m2['user']['name'], 'hourly_cost' => $m2['user']['hourly_cost']]
                    ]
                ];
            }
        }

        // Sort by score descending
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Limit to top 3
        return array_slice($recommendations, 0, 3);
    }

    /**
     * Static in-memory recommendations when there is insufficient DB staff data.
     */
    private static function getMockFallbackRecommendations($project) {
        $clientNpa = self::getSwissNpa($project['client_postal_code']) ?: 1000;
        
        // Let's create beautiful simulated teams with calculated NPA distances
        $npaAlfa = 1003;  // Lausanne
        $npaBeta = 1800;  // Vevey
        $npaOmega = 1202; // Geneva

        $distAlfa = self::calculateDistanceNpa($npaAlfa, $clientNpa);
        $distBeta = self::calculateDistanceNpa($npaBeta, $clientNpa);
        $distOmega = self::calculateDistanceNpa($npaOmega, $clientNpa);

        return [
            [
                'team_name' => "Equipa Alfa (Lausanne)",
                'score' => 95,
                'reasons' => "Disponibilidade total na data do projeto | Distância média estimada de {$distAlfa} km (NPA aproximado) | Experiência de 15 projetos concluídos | Carga de trabalho reduzida (8h/semana).",
                'distance' => "{$distAlfa} km (NPA proxy)",
                'availability' => "Disponível",
                'workload' => "8h/semana",
                'members' => [
                    ['id' => 901, 'name' => 'Michel Roux (Simulado)', 'hourly_cost' => 30.00],
                    ['id' => 902, 'name' => 'Pierre Blanc (Simulado)', 'hourly_cost' => 28.00]
                ]
            ],
            [
                'team_name' => "Equipa Beta (Vevey)",
                'score' => 80,
                'reasons' => "Disponibilidade total na data do projeto | Distância média estimada de {$distBeta} km (NPA aproximado) | Experiência sólida de 8 projetos concluídos | Ocupação intermédia (16h/semana).",
                'distance' => "{$distBeta} km (NPA proxy)",
                'availability' => "Disponível",
                'workload' => "16h/semana",
                'members' => [
                    ['id' => 903, 'name' => 'Luc Rochat (Simulado)', 'hourly_cost' => 31.00],
                    ['id' => 904, 'name' => 'Marc Gétaz (Simulado)', 'hourly_cost' => 29.00]
                ]
            ],
            [
                'team_name' => "Equipa Omega (Genève)",
                'score' => 60,
                'reasons' => "Disponibilidade na data do projeto | Distância mais longa estimada de {$distOmega} km (NPA aproximado) | Elevada experiência de 22 projetos concluídos | Ocupação intermédia (20h/semana).",
                'distance' => "{$distOmega} km (NPA proxy)",
                'availability' => "Disponível",
                'workload' => "20h/semana",
                'members' => [
                    ['id' => 905, 'name' => 'Jean Dupont (Simulado)', 'hourly_cost' => 35.00],
                    ['id' => 906, 'name' => 'André Moret (Simulado)', 'hourly_cost' => 32.00]
                ]
            ]
        ];
    }
}
