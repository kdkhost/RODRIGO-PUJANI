param(
    [switch]$StagedOnly
)

$files = if ($StagedOnly) {
    git diff --cached --name-only --diff-filter=ACM
} else {
    git ls-files
}

$bomFiles = @()
foreach ($file in $files) {
    if (-not (Test-Path $file)) { continue }

    $bytes = [System.IO.File]::ReadAllBytes((Resolve-Path $file))
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        $bomFiles += $file
    }
}

if ($bomFiles.Count -gt 0) {
    Write-Host "Arquivos com UTF-8 BOM detectados:" -ForegroundColor Red
    $bomFiles | ForEach-Object { Write-Host " - $_" -ForegroundColor Red }
    exit 1
}

Write-Host "OK: nenhum arquivo com UTF-8 BOM." -ForegroundColor Green
exit 0
