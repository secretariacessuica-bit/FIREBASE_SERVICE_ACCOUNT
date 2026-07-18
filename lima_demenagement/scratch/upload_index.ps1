$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localFile = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site\index.html"
$targetUrl = "ftp://$hostName/sites/limasolutions.ch/index.html"

Write-Host "Uploading updated index.html..."
$webClient = New-Object System.Net.WebClient
$webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
$webClient.UploadFile($targetUrl, $localFile)
Write-Host "Upload completed!"
