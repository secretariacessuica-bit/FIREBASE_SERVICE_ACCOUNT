Write-Host "Testing all variants..."

$urls = @(
    "https://limasolutions.ch/",
    "https://limasolutions.ch/admin/login.php",
    "https://www.limasolutions.ch/admin/login.php",
    "http://limasolutions.ch/admin/login.php",
    "http://www.limasolutions.ch/admin/login.php",
    "https://limasolutions.ch/facture/index.html"
)

foreach ($url in $urls) {
    try {
        $response = Invoke-WebRequest -Uri $url -Method Head -TimeoutSec 5 -ErrorAction Stop
        Write-Host "URL: $url -> Success! Status: $($response.StatusCode)"
        if ($response.Headers.Location) {
            Write-Host "  Redirects to: $($response.Headers.Location)"
        }
    } catch {
        Write-Host "URL: $url -> Failed: $_"
    }
}
