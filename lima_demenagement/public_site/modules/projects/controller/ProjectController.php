<?php
// LIMA Solutions ERP - Projects & Tasks Controller

class ProjectController {
    private $projectModel;

    public function __construct($projectModel) {
        $this->projectModel = $projectModel;
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
     * Validates project data.
     */
    public function validateProject($data) {
        $errors = [];
        if (empty($data['name'])) {
            $errors[] = "Le nom du projet est obligatoire.";
        }
        if (empty($data['client_id'])) {
            $errors[] = "Le client est obligatoire.";
        }
        if (!empty($data['estimated_hours']) && !is_numeric($data['estimated_hours'])) {
            $errors[] = "Les heures estimées doivent être un nombre.";
        }
        if (!empty($data['budget']) && !is_numeric($data['budget'])) {
            $errors[] = "Le budget doit être un nombre.";
        }
        return $errors;
    }

    /**
     * Validates task data.
     */
    public function validateTask($data) {
        $errors = [];
        if (empty($data['title'])) {
            $errors[] = "Le titre de la tâche est obligatoire.";
        }
        if (empty($data['project_id'])) {
            $errors[] = "Le projet est obligatoire.";
        }
        if (!empty($data['estimated_hours']) && !is_numeric($data['estimated_hours'])) {
            $errors[] = "Les heures estimées doivent être un nombre.";
        }
        return $errors;
    }
}
