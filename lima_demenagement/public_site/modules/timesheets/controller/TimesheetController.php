<?php
// LIMA Solutions ERP - Timesheets Controller

class TimesheetController {
    private $timesheetModel;

    public function __construct($timesheetModel) {
        $this->timesheetModel = $timesheetModel;
    }

    /**
     * Sanitizes inputs to prevent XSS.
     */
    public function sanitize($data) {
        $clean = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $clean[$key] = $val;
            } elseif ($val === null) {
                $clean[$key] = null;
            } else {
                $clean[$key] = htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
            }
        }
        return $clean;
    }

    /**
     * Validates timesheet data.
     */
    public function validate($data) {
        $errors = [];
        if (empty($data['project_id'])) {
            $errors[] = "Le projet est obligatoire.";
        }
        if (empty($data['work_date'])) {
            $errors[] = "La date de travail est obligatoire.";
        }
        
        // If times are empty, verify hours
        if (empty($data['start_time']) && empty($data['end_time'])) {
            if (!isset($data['hours']) || !is_numeric($data['hours']) || (float)$data['hours'] <= 0) {
                $errors[] = "Le nombre d'heures doit être supérieur à zéro.";
            }
        } else {
            // If one of start_time / end_time is filled, the other must be too
            if (empty($data['start_time']) || empty($data['end_time'])) {
                $errors[] = "L'heure de début et l'heure de fin doivent être toutes deux renseignées.";
            } else {
                $start = strtotime($data['start_time']);
                $end = strtotime($data['end_time']);
                if ($end <= $start) {
                    $errors[] = "L'heure de fin doit être après l'heure de début.";
                }
            }
        }
        
        if (isset($data['hourly_rate']) && !is_numeric($data['hourly_rate'])) {
            $errors[] = "Le taux horaire doit être un nombre.";
        }

        return $errors;
    }
}
