#!/usr/bin/env bash
# Bootstrap only from the reviewed Git commit:
# git show "$release:scripts/deploy-catalog-images-20261123.sh" | bash -s -- "$release"
set -Eeuo pipefail
umask 022

release=${1:-}
[[ "$release" =~ ^[0-9a-f]{40}$ ]] || { echo 'Usage: deploy-catalog-images-20261123.sh FULL_REVIEWED_COMMIT' >&2; exit 2; }
expected_base=3926def12dfd91003e86c610257d64cd2bdc9fad
checkout=${TOP_TIM_CHECKOUT:-/home/toptimco/public_html}
state_root=${TOP_TIM_IMAGE_DEPLOY_STATE:-$HOME/.cache/top-tim-image-deploy}
php_bin=${TOP_TIM_PHP:-php}

fail() { echo "Deployment stopped: $*" >&2; exit 1; }
trap 'code=$?; echo "Deployment failed at line $LINENO (exit $code). Inspect the current state and resume forward with the same reviewed commit; no rollback was attempted." >&2; exit "$code"' ERR
for command in git sha256sum flock "$php_bin"; do
    command -v "$command" >/dev/null || fail "Missing command: $command"
done
cd "$checkout"
checkout=$(pwd -P)
[[ $(git rev-parse --show-toplevel) == "$checkout" ]] || fail 'Unexpected checkout root.'
[[ $(git branch --show-current) == master ]] || fail 'Production must be on master.'
[[ $(git remote get-url origin) == git@github.com:agmedia/top-tim.git ]] || fail 'The private production SSH origin is required.'
ssh_command=$(git config --get core.sshCommand || true)
[[ "$ssh_command" == *top-tim-github-readonly* && "$ssh_command" == *StrictHostKeyChecking=yes* ]] || fail 'Use the existing read-only deploy key with strict host verification.'

check_dirty() {
    git diff --cached --quiet || fail 'Production has staged changes.'
    local paths path
    paths=$(git diff --name-only)
    while IFS= read -r path; do
        case "$path" in config/mail.php|public/.htaccess|'') ;; *) fail "Unexpected tracked change: $path" ;; esac
    done <<< "$paths"
}
protected_hash() { sha256sum .env config/mail.php public/.htaccess | sha256sum | cut -d' ' -f1; }
check_dirty
[[ -r .env && -r config/mail.php && -r public/.htaccess ]] || fail 'Protected production configuration is missing.'
[[ ! -e storage/framework/down ]] || fail 'The application is already in maintenance; inspect before proceeding.'
protected_now=$(protected_hash)
current=$(git rev-parse HEAD)
[[ "$current" == "$expected_base" || "$current" == "$release" ]] || fail 'HEAD is neither the audited base nor this release.'

mkdir -p "$state_root"
state_root=$(cd "$state_root" && pwd -P)
case "$state_root/" in "$checkout/"*) fail 'Deployment state must remain outside the web checkout.' ;; esac
exec 9>"$state_root/deploy.lock"
flock -n 9 || fail 'Another image deployment is running.'
export GIT_TERMINAL_PROMPT=0
git fetch origin master
[[ $(git rev-parse origin/master) == "$release" ]] || fail 'origin/master differs from the reviewed commit.'
git merge-base --is-ancestor "$expected_base" "$release" || fail 'The release does not descend from the audited production base.'

# Exact scope: no dependency, migration, protected configuration or user media changes.
release_paths=$(git diff --name-only "$expected_base" "$release")
[[ -n "$release_paths" ]] || fail 'The release contains no image fix.'
while IFS= read -r path; do
    case "$path" in
        app/Helpers/Image.php|app/Models/Back/Catalog/Category.php|app/Models/Back/Catalog/Product/ProductImage.php|config/filesystems.php|resources/views/back/catalog/product/edit-photos.blade.php|app/Console/Commands/RepairMediaPermissions.php|tests/Feature/CategoryImageUploadTest.php|tests/Feature/MediaPermissionsTest.php|tests/Feature/ProductImageUploadTest.php|scripts/deploy-catalog-images-20261123.sh|docs/catalog-images-2026-11-23.md) ;;
        *) fail "Release contains an out-of-scope change: $path" ;;
    esac
done <<< "$release_paths"

snapshot="$state_root/$release.protected"
if [[ -f "$snapshot" ]]; then
    [[ $(cat "$snapshot") == "$protected_now" ]] || fail 'Protected settings differ from the original deployment attempt.'
else
    [[ "$current" == "$expected_base" ]] || fail 'A resumed release requires its original protected-settings snapshot.'
    (umask 077; printf '%s\n' "$protected_now" > "$snapshot")
fi
[[ $(git rev-parse HEAD) == "$current" && $(protected_hash) == "$protected_now" ]] || fail 'Production changed during preflight.'
check_dirty

# Source changes arrive exclusively through the exact Git fast-forward.
git merge --ff-only "$release"
"$php_bin" artisan config:cache
"$php_bin" artisan view:cache
"$php_bin" artisan media:repair-permissions category 140 141 142
"$php_bin" artisan media:repair-permissions category 140 141 142 --apply
"$php_bin" artisan media:repair-permissions products 3623
"$php_bin" artisan media:repair-permissions products 3623 --apply

[[ $(git rev-parse HEAD) == "$release" ]] || fail 'The active commit differs from the reviewed release.'
[[ $(protected_hash) == "$protected_now" ]] || fail 'Protected production settings changed.'
check_dirty
printf 'Activated %s; protected settings preserved and only the selected media permissions repaired.\n' "$release"
