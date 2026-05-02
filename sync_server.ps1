$files = @(
    "app/Support/helpers.php",
    "app/Providers/AppServiceProvider.php",
    "app/Http/Controllers/Admin/FormSecurityLogController.php",
    "app/Http/Middleware/ProtectAndAuditFormSubmissions.php",
    "resources/views/admin/form-security-logs/index.blade.php",
    "resources/views/site/layouts/app.blade.php",
    "resources/css/admin.css",
    "resources/css/site.css",
    "resources/js/admin.js",
    "resources/js/site.js"
)

$sshKey = "C:\Users\marce\.ssh\codex_rodrigo_pujani_ed25519"
$remote = "pujani@pujani.kdkhost.com.br"
$port = 1979
$basePath = "public_html"

foreach ($file in $files) {
    $remoteFile = "$basePath/$file"
    $remoteDir = Split-Path $remoteFile -Parent
    Write-Host "Uploading $file..."
    ssh -F nul -i $sshKey -p $port $remote "mkdir -p $remoteDir"
    scp -F nul -i $sshKey -P $port $file "$remote`:$remoteFile"
}

Write-Host "Uploading public/build..."
ssh -F nul -i $sshKey -p $port $remote "mkdir -p $basePath/public/build"
scp -F nul -r -i $sshKey -P $port public/build/* "$remote`:$basePath/public/build/"
