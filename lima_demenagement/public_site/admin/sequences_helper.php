<?php
// LIMA Solutions ERP - Central Sequence Generator Helper

/**
 * Thread-safe sequence code generator for invoices, quotes, payments, etc.
 * 
 * @param int $companyId The active company ID
 * @param string $sequenceKey The identifier key (e.g. 'CLI', 'Q', 'INV', 'PAY')
 * @param PDO $pdo PDO database connection instance
 * @return string The formatted unique sequence code
 */
function generateSequence($companyId, $sequenceKey, $pdo) {
    if (empty($companyId) || empty($sequenceKey)) {
        throw new InvalidArgumentException("Paramètres de séquence manquants.");
    }

    try {
        $ownsTransaction = false;
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $ownsTransaction = true;
        }

        // 1. Lock sequence row exclusively using FOR UPDATE to avoid race conditions
        $stmt = $pdo->prepare("SELECT current_value, prefix, suffix, padding FROM company_sequences 
            WHERE company_id = :cid AND sequence_key = :key LIMIT 1 FOR UPDATE");
        $stmt->execute(['cid' => $companyId, 'key' => $sequenceKey]);
        $sequence = $stmt->fetch();

        // Fallback: If no sequence is configured, insert default row on the fly
        if (!$sequence) {
            $prefix = $sequenceKey . '-';
            $suffix = '';
            $padding = 6;
            $currentValue = 1;

            $insertStmt = $pdo->prepare("INSERT INTO company_sequences 
                (company_id, sequence_key, current_value, prefix, suffix, padding) 
                VALUES (:cid, :key, :val, :prefix, :suffix, :padding)");
            $insertStmt->execute([
                'cid' => $companyId,
                'key' => $sequenceKey,
                'val' => $currentValue,
                'prefix' => $prefix,
                'suffix' => $suffix,
                'padding' => $padding
            ]);
        } else {
            $prefix = $sequence['prefix'] ?? '';
            $suffix = $sequence['suffix'] ?? '';
            $padding = (int)$sequence['padding'];
            $currentValue = (int)$sequence['current_value'] + 1;

            // Update database with the incremented value
            $updateStmt = $pdo->prepare("UPDATE company_sequences SET current_value = :val 
                WHERE company_id = :cid AND sequence_key = :key");
            $updateStmt->execute([
                'val' => $currentValue,
                'cid' => $companyId,
                'key' => $sequenceKey
            ]);
        }

        if ($ownsTransaction) {
            $pdo->commit();
        }

        // Formats sequence string: PREFIX + VALUE (with padding) + SUFFIX
        $formattedValue = str_pad($currentValue, $padding, '0', STR_PAD_LEFT);
        return $prefix . $formattedValue . $suffix;

    } catch (Exception $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new RuntimeException("Erreur de génération de séquence: " . $e->getMessage());
    }
}
