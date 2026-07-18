$hostName = "6o9v7p.ftp.infomaniak.com"
$username = "6o9v7p_LimaSolutions"
$password = "Bara124578."

$localZip = "C:\Users\Wande\Documents\ia\lima_demenagement\public_site.zip"
$localUnzip = "C:\Users\Wande\Documents\ia\lima_demenagement\scratch\unzip.php"

function Upload-FTPFile {
    param (
        [string]$sourceFile,
        [string]$targetUrl
    )
    Write-Host "Uploading $sourceFile to $targetUrl ..."
    try {
        $webClient = New-Object System.Net.WebClient
        $webClient.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        $webClient.UploadFile($targetUrl, $sourceFile)
        Write-Host "Upload successful!"
    } catch {
        Write-Error "Upload failed: $_"
    }
}

function Create-FTPDirectory {
    param (
        [string]$targetUrl
    )
    Write-Host "Creating directory $targetUrl ..."
    try {
        $request = [System.Net.FtpWebRequest]::Create($targetUrl)
        $request.Credentials = New-Object System.Net.NetworkCredential($username, $password)
        $request.Method = [System.Net.WebRequestMethods+Ftp]::MakeDirectory
        $response = $request.GetResponse()
        $response.Close()
        Write-Host "Directory created successfully!"
    } catch {
        Write-Host "Directory may already exist or error: $_"
    }
}

# 1. Create private_lima folder
Create-FTPDirectory -targetUrl "ftp://$hostName/sites/private_lima"

# 2. Upload public_site.zip
Upload-FTPFile -sourceFile $localZip -targetUrl "ftp://$hostName/sites/limasolutions.ch/public_site.zip"

# 3. Upload unzip.php
Upload-FTPFile -sourceFile $localUnzip -targetUrl "ftp://$hostName/sites/limasolutions.ch/unzip.php"

Write-Host "Deployment Upload completed!"
