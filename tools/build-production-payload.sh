#!/usr/bin/env bash

set -euo pipefail

umask 022

if [[ $# -ne 1 ]]; then
    echo "Uso: $0 /caminho/fora-do-repositorio/payload.tar" >&2
    exit 64
fi

repository_root="$(git rev-parse --show-toplevel)"
output_path="$1"

if [[ "$output_path" != /* ]]; then
    output_path="$(pwd)/$output_path"
fi

repository_root="$(realpath -m -- "$repository_root")"
output_path="$(realpath -m -- "$output_path")"

case "$output_path" in
    *.tar) ;;
    *)
        echo "O artefato deve usar a extensão .tar; ZIP não é permitido." >&2
        exit 64
        ;;
esac

if [[ -e "$output_path" ]]; then
    echo "O arquivo de saída já existe; sobrescrita recusada." >&2
    exit 64
fi

case "$output_path" in
    "$repository_root"/*)
        echo "O payload deve ser criado fora do repositório." >&2
        exit 64
        ;;
esac

if [[ -n "$(git -C "$repository_root" status --porcelain)" ]]; then
    echo "A árvore versionada precisa estar limpa antes do empacotamento." >&2
    exit 65
fi

if [[ ! -f "$repository_root/public/build/manifest.json" ]]; then
    echo "Build Vite ausente. Execute npm ci e npm run build antes do empacotamento." >&2
    exit 66
fi

temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/central-juridica-payload.XXXXXX")"

cleanup() {
    case "$temporary_root" in
        "${TMPDIR:-/tmp}"/central-juridica-payload.*)
            rm -rf -- "$temporary_root"
            ;;
        *)
            echo "Diretório temporário inesperado; limpeza automática interrompida." >&2
            ;;
    esac
}

trap cleanup EXIT

staging_root="$temporary_root/staging"
mkdir -p -- "$staging_root"

allowlist=(
    .env.example
    .htaccess
    artisan
    app
    bootstrap
    composer.json
    composer.lock
    config
    database
    lang
    package.json
    package-lock.json
    postcss.config.js
    public
    resources
    routes
    storage
    tailwind.config.js
    vite.config.js
)

git -C "$repository_root" archive --format=tar HEAD -- "${allowlist[@]}" \
    | tar -xf - -C "$staging_root"

rm -rf -- "$staging_root/public/build"
cp -a -- "$repository_root/public/build" "$staging_root/public/build"

required_paths=(
    .env.example
    .htaccess
    artisan
    composer.json
    composer.lock
    package.json
    package-lock.json
    vite.config.js
    app
    bootstrap/app.php
    config/app.php
    database/migrations
    public/index.php
    public/.htaccess
    public/build/manifest.json
    resources
    routes/web.php
    storage/app/.gitignore
)

for required_path in "${required_paths[@]}"; do
    if [[ ! -e "$staging_root/$required_path" ]]; then
        echo "Arquivo obrigatório ausente do payload: $required_path" >&2
        exit 67
    fi
done

forbidden_path='(^|/)(tests|docs|tools|scripts|\.githooks)(/|$)|(^|/)(\.env|\.phpunit\.result\.cache|phpunit\.xml|error_log|testc\.php|puppeteer_test\.cjs|sync_server\.ps1)$|\.(log|sqlite3?|sql|dump|bak|backup|tmp|temp|orig|rej|swp|zip|rar|7z|tar|tgz|gz|bz2|xz|zst)([.-].*)?$'

if find "$staging_root" -mindepth 1 -printf '%P\n' | grep -Eiq "$forbidden_path"; then
    echo "O staging contém caminho proibido pela política de produção." >&2
    exit 68
fi

mkdir -p -- "$(dirname -- "$output_path")"
tar --sort=name --numeric-owner --owner=0 --group=0 -cf "$output_path" -C "$staging_root" .
chmod 0600 "$output_path"

sha256sum "$output_path"
