<?php
// unzip.php - Safe self-deleting extractor
$zipFile = 'public_site.zip';

header('Content-Type: text/plain; charset=utf-8');

if (file_exists($zipFile)) {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        $zip->extractTo(__DIR__);
        $zip->close();
        echo "SUCESSO: Ficheiros do ERP extraídos com sucesso!\n";
        
        // Clean up
        unlink($zipFile);
        echo "Info: public_site.zip removido por segurança.\n";
        
        unlink(__FILE__);
        echo "Info: unzip.php removido por segurança.\n";
    } else {
        echo "ERRO: Falha ao abrir o ficheiro public_site.zip\n";
    }
} else {
    echo "ERRO: O ficheiro public_site.zip não foi encontrado no servidor.\n";
}
