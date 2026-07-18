$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localVersion = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site\version.txt"
$localPing = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site\admin\ping.php"

$targetVersionUrl = "ftp://$hostName/sites/limasolutions.ch/version.txt"
$targetPingUrl = "ftp://$hostName/sites/limasolutions.ch/admin/ping.php"

$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)

Write-Host "Uploading version.txt..."
$webClient.UploadFile($targetVersionUrl, $localVersion)

Write-Host "Uploading admin/ping.php..."
$webClient.UploadFile($targetPingUrl, $localPing)

Write-Host "Upload completed!"
