$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localZip = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site.zip"
$localHtaccess = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site\.htaccess"

$targetZipUrl = "ftp://$hostName/sites/limasolutions.ch/public_site.zip"
$targetHtaccessUrl = "ftp://$hostName/sites/limasolutions.ch/.htaccess"

$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)

Write-Host "Uploading new public_site.zip..."
$webClient.UploadFile($targetZipUrl, $localZip)

Write-Host "Uploading new .htaccess..."
$webClient.UploadFile($targetHtaccessUrl, $localHtaccess)

Write-Host "Upload completed!"
