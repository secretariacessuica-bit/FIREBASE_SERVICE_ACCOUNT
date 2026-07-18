<?php
// restore_files.php - Restore flat structure if /web was not the public root

$siteDir = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch';
$webDir = $siteDir . '/web';

echo "Moving files back to root...\n";
if (file_exists($webDir)) {
    $items = scandir($webDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $source = $webDir . '/' . $item;
        $dest = $siteDir . '/' . $item;
        if (rename($source, $dest)) {
            echo "Restored: $item\n";
        } else {
            echo "Failed to restore: $item\n";
        }
    }
    rmdir($webDir);
    echo "Removed web/ directory.\n";
}

echo "Restoring private_lima...\n";
$oldPrivate = $siteDir . '/private_lima';
$newPrivate = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima';

if (file_exists($oldPrivate)) {
    if (rename($oldPrivate, $newPrivate)) {
        echo "Successfully moved private_lima back to $newPrivate\n";
    } else {
        echo "Failed to move private_lima back to $newPrivate\n";
    }
} else {
    echo "private_lima not found at $oldPrivate\n";
}

echo "Restore complete!\n";
unlink(__FILE__); // self delete
