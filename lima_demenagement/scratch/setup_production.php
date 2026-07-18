<?php
// setup_production.php - Automatic DB Test, Config Writer, and Schema Importer

$dbHost = 'localhost';
$dbName = '6o9v7p_erp';
$password = 'Bara124578.';

// We will test potential usernames
$usernames = ['6o9v7p_LimaSolutions', '6o9v7p_erp', '6o9v7p_lima', '6o9v7p_admin'];
$connectedUser = null;
$pdo = null;

echo "Starting Database Connection Tests...\n";

foreach ($usernames as $username) {
    try {
        echo "Testing user: $username ... ";
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
        $testPdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        echo "SUCCESS!\n";
        $connectedUser = $username;
        $pdo = $testPdo;
        break;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
}

if (!$connectedUser) {
    echo "ERROR: Could not connect to database with any username.\n";
    exit(1);
}

// 1. Write private_lima/config.php
$privateDir = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima';
if (!file_exists($privateDir)) {
    mkdir($privateDir, 0750, true);
}

$configContent = "<?php
// LIMA Solutions ERP - Private Credentials File
// Automatically generated during setup

define('SECURE_DB_HOST', '$dbHost');
define('SECURE_DB_NAME', '$dbName');
define('SECURE_DB_USER', '$connectedUser');
define('SECURE_DB_PASS', '$password');
";

file_put_contents("$privateDir/config.php", $configContent);
chmod("$privateDir/config.php", 0640);
echo "Config file created successfully at $privateDir/config.php\n";

// 2. Import Schema
$schemaFile = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/db/schema.sql';
if (!file_exists($schemaFile)) {
    echo "ERROR: Schema file not found at $schemaFile\n";
    exit(1);
}

echo "Importing schema from $schemaFile ...\n";
try {
    $sql = file_get_contents($schemaFile);
    
    // Remove comments and split by semicolon (simple parser)
    // Note: since schema.sql might contain triggers or delimiters, we will execute it in chunks or try executing it directly if PDO allows it (multi-query is enabled by default in PDO mysql depending on driver).
    // Let's use exec which handles multi-query in most mysql PDO drivers.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);
    $pdo->exec($sql);
    echo "Schema imported successfully!\n";
} catch (Exception $e) {
    echo "ERROR importing schema: " . $e->getMessage() . "\n";
    exit(1);
}

// 3. Run Seeder
echo "Running seeder...\n";
try {
    // We change directory to the seed directory to let require_once resolve config.php
    chdir('/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/api/v1');
    ob_start();
    include 'seed.php';
    $seedOutput = ob_get_clean();
    echo "Seeder Output:\n$seedOutput\n";
} catch (Exception $e) {
    echo "ERROR running seeder: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Database and Config Setup completed successfully!\n";
