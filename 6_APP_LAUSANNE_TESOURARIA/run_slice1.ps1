<#
.SYNOPSIS
Script para inicializar o backend e frontend do MVP CME Lausanne (Slice 1).
#>

$ApiDir = ".\tesouraria_cme_api"
$AppDir = ".\tesouraria_cme_app"

Write-Host "Iniciando CME Lausanne MVP - Slice 1..." -ForegroundColor Cyan

# 1. Iniciar Spring Boot API em background
Write-Host "Iniciando Backend (Spring Boot na porta 8080)..." -ForegroundColor Yellow
Start-Process -FilePath "mvn" -ArgumentList "spring-boot:run" -WorkingDirectory $ApiDir -WindowStyle Minimized

# Espera alguns segundos para a API subir
Write-Host "Aguardando a inicializacao da API..." -ForegroundColor DarkGray
Start-Sleep -Seconds 10

# 2. Iniciar Flutter App (Desktop Windows)
Write-Host "Iniciando Frontend (Flutter Desktop)..." -ForegroundColor Green
Set-Location -Path $AppDir

# Instala as dependencias, caso seja a primeira vez
Write-Host "Baixando dependencias do Flutter..." -ForegroundColor DarkGray
flutter pub get

# Roda em ambiente Windows
Write-Host "Abrindo a Interface..." -ForegroundColor Green
flutter run -d windows
