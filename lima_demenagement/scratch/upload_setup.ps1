$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localSetup = "C:\Users\Wande\Documents\ia\lima_demenagement\scratch\setup_production.php"
$targetUrl = "ftp://$hostName/sites/limasolutions.ch/setup_production.php"

Write-Host "Uploading setup script..."
$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$webClient.UploadFile($targetUrl, $localSetup)
Write-Host "Upload completed!"
