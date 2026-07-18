$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localFile = "C:\Users\Wande\Documents\ia\lima_demenagement\scratch\move_files.php"
$targetUrl = "ftp://$hostName/sites/limasolutions.ch/move_files.php"

Write-Host "Uploading move_files.php..."
$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$webClient.UploadFile($targetUrl, $localFile)
Write-Host "Upload completed!"
