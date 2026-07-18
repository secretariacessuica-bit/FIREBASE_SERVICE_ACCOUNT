$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$targetUrl = "ftp://$hostName/sites/limasolutions.ch/.htaccess"
$localFile = "C:\Users\Wande\Documents\ia\lima_demenagement\scratch\htaccess.txt"

Write-Host "Downloading .htaccess..."
try {
    $webClient = New-Object System.Net.WebClient
    $webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
    $webClient.DownloadFile($targetUrl, $localFile)
    Write-Host "Download successful!"
} catch {
    Write-Host "No .htaccess found or download failed: $_"
}
