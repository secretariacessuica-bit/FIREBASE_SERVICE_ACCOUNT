$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localZip = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site.zip"
$targetUrl = "ftp://$hostName/sites/limasolutions.ch/public_site.zip"

# 1. Upload new zip
Write-Host "Uploading new Linux-compatible public_site.zip..."
$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$webClient.UploadFile($targetUrl, $localZip)
Write-Host "Upload completed!"
