$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localFile = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site\ANTES_DE_MEXER_LEIA.md"
$targetUrl = "ftp://$hostName/sites/limasolutions.ch/ANTES_DE_MEXER_LEIA.md"

Write-Host "Uploading ANTES_DE_MEXER_LEIA.md..."
$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$webClient.UploadFile($targetUrl, $localFile)
Write-Host "Upload completed!"
