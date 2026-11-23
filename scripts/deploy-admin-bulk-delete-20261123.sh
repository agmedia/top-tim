#!/usr/bin/env bash
# Bootstrap from the reviewed Git object, after its exact commit has been pushed.
set -Eeuo pipefail
umask 022

release=${1:-}
[[ "$release" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full reviewed commit is required.' >&2; exit 2; }
expected_base=434f4dd99b174d6055039896033cfbd93586433c
checkout=${TOP_TIM_CHECKOUT:-/home/toptimco/public_html}
state_root=${TOP_TIM_BULK_DEPLOY_STATE:-$HOME/.cache/top-tim-bulk-deploy}
php_bin=${TOP_TIM_PHP:-php}
fail() { echo "Deployment stopped: $*" >&2; exit 1; }
trap 'code=$?; echo "Deployment failed at line $LINENO (exit $code). Inspect and resume forward with the same release; no rollback or catalogue deletion was attempted." >&2; exit "$code"' ERR
for command in git sha256sum flock "$php_bin"; do command -v "$command" >/dev/null || fail "Missing command: $command"; done
cd "$checkout"
checkout=$(pwd -P)
[[ $(git rev-parse --show-toplevel) == "$checkout" && $(git branch --show-current) == master ]] || fail 'Unexpected production checkout or branch.'
[[ $(git remote get-url origin) == git@github.com:agmedia/top-tim.git ]] || fail 'The private production origin is required.'
ssh_command=$(git config --get core.sshCommand || true)
[[ "$ssh_command" == *top-tim-github-readonly* && "$ssh_command" == *StrictHostKeyChecking=yes* ]] || fail 'The existing read-only deploy key and strict host verification are required.'
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
[[ -r .env && -r config/mail.php && -r public/.htaccess && ! -e storage/framework/down ]] || fail 'Protected settings are missing or maintenance is active.'
protected_now=$(protected_hash)
current=$(git rev-parse HEAD)
[[ "$current" == "$expected_base" || "$current" == "$release" ]] || fail 'HEAD differs from the reviewed base or release.'
mkdir -p "$state_root"
state_root=$(cd "$state_root" && pwd -P)
case "$state_root/" in "$checkout/"*) fail 'Deployment state must remain outside the web checkout.' ;; esac
exec 9>"$state_root/deploy.lock"
flock -n 9 || fail 'Another bulk-delete deployment is running.'
export GIT_TERMINAL_PROMPT=0
git fetch origin master
[[ $(git rev-parse origin/master) == "$release" ]] || fail 'origin/master differs from the reviewed release.'
git merge-base --is-ancestor "$expected_base" "$release" || fail 'Release has an unexpected base.'
paths=$(git diff --name-only "$expected_base" "$release")
[[ -n "$paths" ]] || fail 'The release is empty.'
while IFS= read -r path; do
    case "$path" in
        app/Http/Controllers/Api/v2/ProductController.php|app/Http/Controllers/Back/Catalog/ProductController.php|app/Http/Controllers/Back/Marketing/ActionController.php|app/Http/Requests/BulkDeleteRequest.php|app/Services/CatalogBulkDeletion.php|app/Models/Back/Orders/Order.php|resources/views/back/catalog/product/index.blade.php|resources/views/back/marketing/action/index.blade.php|resources/views/back/layouts/partials/bulk-delete.blade.php|resources/views/back/order/show.blade.php|resources/views/front/customer/moje-narudzbe.blade.php|routes/web.php|tests/Feature/CatalogBulkDeletionTest.php|tests/Feature/HistoricalOrderProductTest.php|tests/Feature/DeletedCartProductTest.php|tests/JavaScript/admin-bulk-delete.test.cjs|scripts/deploy-admin-bulk-delete-20261123.sh|docs/admin-bulk-delete-2026-11-23.md) ;;
        *) fail "Out-of-scope release path: $path" ;;
    esac
done <<< "$paths"
snapshot="$state_root/$release.protected"
if [[ -f "$snapshot" ]]; then
    [[ $(cat "$snapshot") == "$protected_now" ]] || fail 'Protected settings changed after the original attempt.'
else
    [[ "$current" == "$expected_base" ]] || fail 'A resumed release needs its original settings snapshot.'
    (umask 077; printf '%s\n' "$protected_now" > "$snapshot")
fi
[[ $(git rev-parse HEAD) == "$current" && $(protected_hash) == "$protected_now" ]] || fail 'Production changed during preflight.'
check_dirty
git merge --ff-only "$release"
"$php_bin" artisan route:clear
"$php_bin" artisan view:cache
"$php_bin" -r '
$checked=false;
register_shutdown_function(function () use (&$checked) { if (!$checked) { fwrite(STDERR,"POST route verification did not finish.".PHP_EOL); exit(1); } });
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
// Use a canonical request only for this read-only CLI inspection; keep production settings intact.
$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\SetRequestForConsole::class, function ($app) { $app->instance("request",Illuminate\Http\Request::create("https://www.top-tim.com/")); });
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (["products.bulk.destroy","actions.bulk.destroy","products.bulk.action"] as $name) {
    $route=Illuminate\Support\Facades\Route::getRoutes()->getByName($name);
    if (!$route || $route->methods() !== ["POST"]) { fwrite(STDERR,"Missing protected POST route: ".$name.PHP_EOL); exit(1); }
    echo $name,": POST",PHP_EOL;
}
$checked=true;
'
[[ $(git rev-parse HEAD) == "$release" && $(protected_hash) == "$protected_now" ]] || fail 'Release or protected settings changed.'
check_dirty
printf 'Activated %s; catalogue data untouched during deployment.\n' "$release"
