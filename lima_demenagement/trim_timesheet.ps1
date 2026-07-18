$f = Join-Path $PSScriptRoot "public_site\modules\timesheets\model\Timesheet.php"
$lines = Get-Content $f
$clean = $lines[0..746]
[System.IO.File]::WriteAllLines($f, $clean, [System.Text.Encoding]::UTF8)
$count = (Get-Content $f).Count
Write-Host "Done. File now has $count lines."
