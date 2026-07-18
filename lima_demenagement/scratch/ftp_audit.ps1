$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

function Get-FtpDirectoryListing {
    param (
        [string]$uriPath
    )
    $uri = "ftp://$hostName/$uriPath"
    try {
        $request = [System.Net.FtpWebRequest]::Create($uri)
        $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectoryDetails
        $request.Timeout = 5000
        
        $response = $request.GetResponse()
        $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
        $output = $reader.ReadToEnd()
        $reader.Close()
        $response.Close()
        
        return $output
    } catch {
        return "ERROR: $_"
    }
}

Write-Host "--- Auditing limasolutions.ch DIRECTORIES over FTP ---"

$adminFiles = Get-FtpDirectoryListing -uriPath "sites/limasolutions.ch/admin/"
Write-Host "Files in sites/limasolutions.ch/admin/:"
Write-Host $adminFiles

$apiFiles = Get-FtpDirectoryListing -uriPath "sites/limasolutions.ch/api/"
Write-Host "Files in sites/limasolutions.ch/api/:"
Write-Host $apiFiles

$factureFiles = Get-FtpDirectoryListing -uriPath "sites/limasolutions.ch/facture/"
Write-Host "Files in sites/limasolutions.ch/facture/:"
Write-Host $factureFiles
