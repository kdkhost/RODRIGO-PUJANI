param(
    [switch]$StagedOnly
)

$files = if ($StagedOnly) {
    git diff --cached --name-only --diff-filter=ACM
} else {
    git ls-files
}

$bomFiles = @()
$invalidUtf8Files = @()
$mojibakeFiles = @()
$utf8 = New-Object System.Text.UTF8Encoding($false, $true)
$textExtensions = @(
    '.php', '.js', '.ts', '.css', '.json', '.xml', '.yml', '.yaml', '.sql',
    '.md', '.ps1', '.sh', '.env', '.txt', '.html', '.htaccess', '.editorconfig', '.gitattributes'
)
$mojibakePatterns = @(
    [string][char]0x00C3,
    [string][char]0x00C2,
    ([string][char]0x00E2 + [char]0x20AC),
    [string][char]0xFFFD,
    ('\' + 'u00c3'),
    ('\' + 'u00C3')
)

foreach ($file in $files) {
    if (-not (Test-Path $file)) { continue }

    $resolvedPath = (Resolve-Path $file).Path
    $bytes = [System.IO.File]::ReadAllBytes($resolvedPath)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $bomFiles += $file
    }

    $extension = [System.IO.Path]::GetExtension($file).ToLowerInvariant()
    $name = [System.IO.Path]::GetFileName($file).ToLowerInvariant()
    $isText = $textExtensions -contains $extension -or $textExtensions -contains ".$name"

    if (-not $isText) { continue }

    try {
        $content = $utf8.GetString($bytes)
    } catch {
        $invalidUtf8Files += $file
        continue
    }

    if ($mojibakePatterns | Where-Object { $content.Contains($_) }) {
        $mojibakeFiles += $file
    }
}

if ($bomFiles.Count -gt 0) {
    Write-Host "Arquivos com UTF-8 BOM detectados:" -ForegroundColor Red
    $bomFiles | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
}

if ($invalidUtf8Files.Count -gt 0) {
    Write-Host "Arquivos textuais que nao sao UTF-8 valido:" -ForegroundColor Red
    $invalidUtf8Files | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
}

if ($mojibakeFiles.Count -gt 0) {
    Write-Host "Arquivos com padroes conhecidos de mojibake:" -ForegroundColor Red
    $mojibakeFiles | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
}

if ($bomFiles.Count -gt 0 -or $invalidUtf8Files.Count -gt 0 -or $mojibakeFiles.Count -gt 0) {
    exit 1
}

Write-Host "OK: arquivos textuais em UTF-8 sem BOM e sem mojibake conhecido." -ForegroundColor Green
