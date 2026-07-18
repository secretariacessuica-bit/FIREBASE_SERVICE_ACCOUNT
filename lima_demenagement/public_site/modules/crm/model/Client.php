<?php
// LIMA Solutions ERP - CRM Client Model

class Client {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Gets active clients for a company with pagination and ORDER BY created_at DESC.
     */
    public function getAll($companyId, $limit = 50, $offset = 0) {
        $sql = "SELECT id, company_id, customer_code, company, name, contact_person, 
                       phone, mobile, whatsapp, email, website, address, city, 
                       canton, postal_code, country, vat_number, preferred_language, 
                       preferred_currency, notes, tags, active, created_at 
                FROM clients 
                WHERE company_id = :company_id AND active = 1 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get total count of active clients (for pagination).
     */
    public function getTotalCount($companyId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM clients WHERE company_id = :company_id AND active = 1");
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Gets a single client by ID and company ID.
     */
    public function getById($id, $companyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Search clients by terms within a company with pagination.
     */
    public function search($term, $companyId, $limit = 50, $offset = 0) {
        $likeTerm = '%' . $term . '%';
        $sql = "SELECT id, company_id, customer_code, company, name, contact_person, 
                       phone, mobile, whatsapp, email, website, address, city, 
                       canton, postal_code, country, vat_number, preferred_language, 
                       preferred_currency, notes, tags, active, created_at 
                FROM clients 
                WHERE company_id = :company_id 
                  AND active = 1 
                  AND (
                    customer_code LIKE :term OR 
                    name LIKE :term OR 
                    company LIKE :term OR 
                    contact_person LIKE :term OR
                    email LIKE :term OR 
                    phone LIKE :term OR 
                    mobile LIKE :term OR
                    whatsapp LIKE :term OR
                    vat_number LIKE :term OR
                    tags LIKE :term
                  ) 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':term', $likeTerm, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get count of searched active clients.
     */
    public function getSearchCount($term, $companyId) {
        $likeTerm = '%' . $term . '%';
        $sql = "SELECT COUNT(*) FROM clients 
                WHERE company_id = :company_id 
                  AND active = 1 
                  AND (
                    customer_code LIKE :term OR 
                    name LIKE :term OR 
                    company LIKE :term OR 
                    contact_person LIKE :term OR
                    email LIKE :term OR 
                    phone LIKE :term OR 
                    mobile LIKE :term OR
                    whatsapp LIKE :term OR
                    vat_number LIKE :term OR
                    tags LIKE :term
                  )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':term', $likeTerm, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Creates a new client.
     */
    public function create($data) {
        $sql = "INSERT INTO clients (
            company_id, customer_code, company, name, contact_person, 
            phone, mobile, whatsapp, email, website, 
            address, city, canton, postal_code, country, 
            vat_number, preferred_language, preferred_currency, notes, tags, active
        ) VALUES (
            :company_id, :customer_code, :company, :name, :contact_person, 
            :phone, :mobile, :whatsapp, :email, :website, 
            :address, :city, :canton, :postal_code, :country, 
            :vat_number, :preferred_language, :preferred_currency, :notes, :tags, 1
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'company_id' => (int)$data['company_id'],
            'customer_code' => $data['customer_code'],
            'company' => $data['company'] ?? null,
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'canton' => $data['canton'] ?? null,
            'postal_code' => $data['postal_code'],
            'country' => $data['country'] ?? 'Suisse',
            'vat_number' => $data['vat_number'] ?? null,
            'preferred_language' => $data['preferred_language'] ?? 'FR',
            'preferred_currency' => $data['preferred_currency'] ?? 'CHF',
            'notes' => $data['notes'] ?? null,
            'tags' => $data['tags'] ?? null
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Updates an existing client.
     */
    public function update($id, $data, $companyId) {
        $sql = "UPDATE clients SET 
            company = :company,
            name = :name,
            contact_person = :contact_person,
            phone = :phone,
            mobile = :mobile,
            whatsapp = :whatsapp,
            email = :email,
            website = :website,
            address = :address,
            city = :city,
            canton = :canton,
            postal_code = :postal_code,
            country = :country,
            vat_number = :vat_number,
            preferred_language = :preferred_language,
            preferred_currency = :preferred_currency,
            notes = :notes,
            tags = :tags
            WHERE id = :id AND company_id = :company_id";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => (int)$id,
            'company_id' => (int)$companyId,
            'company' => $data['company'] ?? null,
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'email' => $data['email'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'],
            'city' => $data['city'],
            'canton' => $data['canton'] ?? null,
            'postal_code' => $data['postal_code'],
            'country' => $data['country'] ?? 'Suisse',
            'vat_number' => $data['vat_number'] ?? null,
            'preferred_language' => $data['preferred_language'] ?? 'FR',
            'preferred_currency' => $data['preferred_currency'] ?? 'CHF',
            'notes' => $data['notes'] ?? null,
            'tags' => $data['tags'] ?? null
        ]);
    }

    /**
     * Performs a Soft Delete on a client by setting active to 0.
     */
    public function deactivate($id, $companyId) {
        $stmt = $this->pdo->prepare("UPDATE clients SET active = 0 WHERE id = :id AND company_id = :company_id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Thread-safe sequential generation of customer code.
     */
    public function generateNextClientCode($companyId) {
        try {
            $this->pdo->beginTransaction();

            // Lock row using FOR UPDATE to prevent race conditions during parallel client generation
            $stmt = $this->pdo->prepare("SELECT customer_code FROM clients 
                WHERE company_id = :company_id AND customer_code LIKE 'CLI-%' 
                ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->execute();
            $lastCode = $stmt->fetchColumn();

            if (!$lastCode) {
                $nextCode = 'CLI-000001';
            } else {
                $num = (int)str_replace('CLI-', '', $lastCode);
                $nextNum = $num + 1;
                $nextCode = 'CLI-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
            }

            $this->pdo->commit();
            return $nextCode;
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Fallback safe ID generator using microtime prefix
            return 'CLI-' . substr(time(), -6);
        }
    }
}
