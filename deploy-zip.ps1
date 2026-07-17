# ============================================================
#  deploy-zip.ps1  --  Gera o .zip de deploy para hospedagem
#  compartilhada excluindo arquivos desnecessarios / sensiveis.
#
#  Uso:
#    .\deploy-zip.ps1
#    .\deploy-zip.ps1 -SkipBuild      (pula o npm run build)
#    .\deploy-zip.ps1 -OutputPath "C:\deploy\meu-app.zip"
# ============================================================

param(
    [switch]$SkipBuild,
    [string]$OutputPath = ""
)

$ErrorActionPreference = "Stop"

# -- Configuracoes -------------------------------------------
$ProjectRoot = $PSScriptRoot
$Timestamp   = Get-Date -Format "yyyyMMdd_HHmmss"
$ZipName     = if ($OutputPath -ne "") { $OutputPath } else { Join-Path $ProjectRoot "deploy_$Timestamp.zip" }

# Pastas que NAO devem ter seu CONTEUDO incluido (serao criadas vazias no ZIP)
$EmptyDirs = @(
    "storage\framework\cache",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)

# Pastas que devem ser TOTALMENTE excluidas
$ExcludeDirs = @(
    ".git",
    ".github",
    ".cursor",
    "node_modules",
    "tests"
)

# Arquivos que NAO devem entrar no zip
$ExcludeFiles = @(
    ".env",
    ".editorconfig",
    ".gitattributes",
    ".gitignore",
    ".npmrc",
    ".prettierignore",
    ".prettierrc",
    "AGENTS.md",
    "boost.json",
    "eslint.config.js",
    "nota.txt",
    "package.json",
    "package-lock.json",
    "phpunit.xml",
    "pint.json",
    "tsconfig.json",
    "vite.config.ts",
    "components.json",
    "deploy-zip.ps1",
    "deploy-zip.bat"
)

# -- Painel de confirmacao -----------------------------------
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "    INOVAFORM -- Deploy ZIP Builder" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Raiz do projeto : $ProjectRoot" -ForegroundColor Gray
Write-Host "  Arquivo de saida: $ZipName"      -ForegroundColor Yellow
Write-Host ""

# -- 1. Build do frontend ------------------------------------
if (-not $SkipBuild) {
    Write-Host "[1/4] Executando npm run build..." -ForegroundColor Cyan
    Push-Location $ProjectRoot
    npm run build
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERRO: npm run build falhou. Abortando." -ForegroundColor Red
        exit 1
    }
    Pop-Location
    Write-Host "      Build concluido." -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "[1/4] Build ignorado (-SkipBuild)." -ForegroundColor Yellow
    Write-Host ""
}

# -- 2. Garantir estrutura de pastas do storage --------------
Write-Host "[2/4] Preparando estrutura do storage e cache..." -ForegroundColor Cyan

# Limpar cache local antes de zipar para evitar caminhos antigos
if (Test-Path "$ProjectRoot\artisan") {
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
}

$StorageDirs = @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)
foreach ($dir in $StorageDirs) {
    $fullPath = Join-Path $ProjectRoot $dir
    if (-not (Test-Path $fullPath)) {
        New-Item -ItemType Directory -Path $fullPath -Force | Out-Null
    }
    $gitkeep = Join-Path $fullPath ".gitkeep"
    if (-not (Test-Path $gitkeep)) {
        New-Item -ItemType File -Path $gitkeep -Force | Out-Null
    }
}
Write-Host "      Pronto." -ForegroundColor Green
Write-Host ""

# -- 3. Coletar arquivos a incluir ---------------------------
Write-Host "[3/4] Coletando arquivos para o zip..." -ForegroundColor Cyan

$AllFiles = Get-ChildItem -Path $ProjectRoot -Recurse -File

$FilesToZip = $AllFiles | Where-Object {
    $file = $_
    $relativePath = $file.FullName.Substring($ProjectRoot.Length + 1)

    # 1. Verificar se esta em alguma pasta TOTALMENTE excluida
    foreach ($dir in $ExcludeDirs) {
        $normalizedDir = $dir.TrimEnd('\') + '\'
        if ($relativePath.StartsWith($normalizedDir, [System.StringComparison]::OrdinalIgnoreCase)) {
            return $false
        }
    }

    # 2. Verificar se esta em uma pasta que deve ser VAZIA (permitir apenas .gitkeep)
    foreach ($dir in $EmptyDirs) {
        $normalizedDir = $dir.TrimEnd('\') + '\'
        if ($relativePath.StartsWith($normalizedDir, [System.StringComparison]::OrdinalIgnoreCase)) {
            if ($file.Name -ieq ".gitkeep") { return $true }
            return $false
        }
    }

    # 3. Verificar se e um arquivo excluido pelo nome
    foreach ($excFile in $ExcludeFiles) {
        if ($file.Name -ieq $excFile) { return $false }
    }

    # 4. Excluir zips de deploy antigos que estejam na raiz
    if ($file.Extension -ieq ".zip" -and $file.DirectoryName -ieq $ProjectRoot) {
        return $false
    }

    return $true
}

Write-Host "      $($FilesToZip.Count) arquivos encontrados." -ForegroundColor Gray
Write-Host ""

# -- 4. Criar o ZIP ------------------------------------------
Write-Host "[4/4] Criando o arquivo ZIP..." -ForegroundColor Cyan

if (Test-Path $ZipName) {
    Remove-Item $ZipName -Force
    Write-Host "      Zip anterior removido." -ForegroundColor Gray
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($ZipName, 'Create')

$processed = 0
foreach ($file in $FilesToZip) {
    $relativePath = $file.FullName.Substring($ProjectRoot.Length + 1)
    # Barras normais dentro do zip (compativel com Linux/cPanel)
    $entryName = $relativePath.Replace('\', '/')
    try {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zip,
            $file.FullName,
            $entryName,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
        $processed++
        if ($processed % 300 -eq 0) {
            Write-Host "      ... $processed arquivos adicionados" -ForegroundColor Gray
        }
    } catch {
        Write-Host "      AVISO - Ignorado: $relativePath" -ForegroundColor Yellow
    }
}

$zip.Dispose()

# -- Resultado -----------------------------------------------
$zipInfo = Get-Item $ZipName
$sizeMB  = [math]::Round($zipInfo.Length / 1MB, 2)

Write-Host ""
Write-Host "================================================" -ForegroundColor Green
Write-Host "    ZIP CRIADO COM SUCESSO!" -ForegroundColor Green
Write-Host "================================================" -ForegroundColor Green
Write-Host ""
Write-Host "  Arquivo : $ZipName"   -ForegroundColor Yellow
Write-Host "  Tamanho : $sizeMB MB" -ForegroundColor Yellow
Write-Host "  Arquivos: $processed" -ForegroundColor Yellow
Write-Host ""
Write-Host "  LEMBRETE:" -ForegroundColor Magenta
Write-Host "  - Crie e configure o .env na hospedagem"           -ForegroundColor Magenta
Write-Host "  - Execute: php artisan key:generate"               -ForegroundColor Magenta
Write-Host "  - Execute: php artisan migrate --force"            -ForegroundColor Magenta
Write-Host "  - Execute: php artisan storage:link"               -ForegroundColor Magenta
Write-Host "  - Certifique-se de apontar o DocumentRoot para public/" -ForegroundColor Magenta
Write-Host ""
