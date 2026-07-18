$root = $PSScriptRoot
$sourceDir = Join-Path $root "public_site"
$docsDir = Join-Path $root "docs"
$changelogFile = Join-Path $root "CHANGELOG.md"
$readmeFile = Join-Path $root "README.md"
$destinationZip = Join-Path $root "public_site.zip"

Write-Host "================================================"
Write-Host "  LIMA Solutions ERP - RC 1.0 Package Builder"
Write-Host "================================================"

if (Test-Path -Path $destinationZip) {
    Remove-Item -Path $destinationZip -Force
    Write-Host "[OK] Pacote anterior removido."
}

$stagingDir = Join-Path $env:TEMP ("lima_erp_" + (Get-Random))
New-Item -ItemType Directory -Path $stagingDir | Out-Null

Copy-Item -Path "$sourceDir\*" -Destination $stagingDir -Recurse -Force
Write-Host "[OK] Conteudo de public_site copiado."

$stagingDocs = Join-Path $stagingDir "docs"
New-Item -ItemType Directory -Path $stagingDocs -ErrorAction SilentlyContinue | Out-Null
if (Test-Path $docsDir) {
    Copy-Item -Path "$docsDir\*" -Destination $stagingDocs -Recurse -Force
    Write-Host "[OK] Documentacao incluida."
}

if (Test-Path $changelogFile) {
    Copy-Item -Path $changelogFile -Destination $stagingDir -Force
}
if (Test-Path $readmeFile) {
    Copy-Item -Path $readmeFile -Destination $stagingDir -Force
}
Write-Host "[OK] CHANGELOG.md e README.md incluidos."

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($stagingDir, $destinationZip)

Remove-Item -Path $stagingDir -Recurse -Force

if (Test-Path -Path $destinationZip) {
    $bytes = (Get-Item $destinationZip).Length
    $kb = [math]::Round($bytes / 1024, 1)
    Write-Host "[SUCESSO] public_site.zip gerado com sucesso. Tamanho: $bytes bytes ($kb KB)"
} else {
    Write-Host "[ERRO] Falha ao gerar o pacote."
    exit 1
}
