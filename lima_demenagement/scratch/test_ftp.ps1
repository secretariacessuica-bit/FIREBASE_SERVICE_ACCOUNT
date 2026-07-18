$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

Write-Host "Listing /sites/limasolutions.ch/..."
try {
    $ftpRequest = [System.Net.FtpWebRequest]::Create("ftp://$hostName/sites/limasolutions.ch/")
    $ftpRequest.Credentials = New-Object System.Net.NetworkCredential($username, $password)
    $ftpRequest.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory
    $ftpRequest.Timeout = 10000
    
    $response = $ftpRequest.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $directoryList = $reader.ReadToEnd()
    
    Write-Host "Directory listing:"
    Write-Host $directoryList
    
    $reader.Close()
    $response.Close()
} catch {
    Write-Host "FTP List failed: $_"
}
