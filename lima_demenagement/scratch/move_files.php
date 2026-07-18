<?php
// move_files.php - Safely restructure directories to support /web public root

$siteDir = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch';
$webDir = $siteDir . '/web';

echo "Creating web directory...\n";
if (!file_exists($webDir)) {
    mkdir($webDir, 0755, true);
}

echo "Moving site files into web/ ...\n";
$items = scandir($siteDir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..' || $item === 'web') {
        continue;
    }
    $source = $siteDir . '/' . $item;
    $dest = $webDir . '/' . $item;
    
    if (rename($source, $dest)) {
        echo "Moved: $item\n";
    } else {
        echo "Failed to move: $item\n";
    }
}

echo "Relocating private_lima...\n";
$oldPrivate = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima';
$newPrivate = $siteDir . '/private_lima';

if (file_exists($oldPrivate)) {
    if (rename($oldPrivate, $newPrivate)) {
        echo "Successfully moved private_lima to $newPrivate\n";
    } else {
        echo "Failed to move private_lima to $newPrivate\n";
    }
} else {
    echo "private_lima not found at $oldPrivate (it might already be in the new location).\n";
}

echo "Structuring complete!\n";
unlink(__FILE__); // self delete
