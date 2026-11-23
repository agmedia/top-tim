#!/usr/bin/env bash
# Bootstrap from the reviewed Git object, after its exact commit has been pushed.
set -Eeuo pipefail
umask 022

release=${1:-}
[[ "$release" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full reviewed commit is required.' >&2; exit 2; }
expected_base=63b753f9c420e3e7f69beea17ac480d2ec9170b4
checkout=${TOP_TIM_CHECKOUT:-/home/toptimco/public_html}
state_root=${TOP_TIM_EVERYONE_DISCOUNT_DEPLOY_STATE:-$HOME/.cache/top-tim-everyone-discount-deploy}
php_bin=${TOP_TIM_PHP:-php}
fail() { echo "Deployment stopped: $*" >&2; exit 1; }
trap 'code=$?; echo "Deployment failed at line $LINENO (exit $code). Inspect and resume forward with the same release; no rollback was attempted. If maintenance is active, resume this same reviewed release to finish and restore service." >&2; exit "$code"' ERR
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
[[ -r .env && -r config/mail.php && -r public/.htaccess ]] || fail 'Protected settings are missing.'
protected_now=$(protected_hash)
current=$(git rev-parse HEAD)
[[ "$current" == "$expected_base" || "$current" == "$release" ]] || fail 'HEAD differs from the reviewed base or release.'
mkdir -p "$state_root"
state_root=$(cd "$state_root" && pwd -P)
case "$state_root/" in "$checkout/"*) fail 'Deployment state must remain outside the web checkout.' ;; esac
exec 9>"$state_root/deploy.lock"
flock -n 9 || fail 'Another everyone-discount deployment is running.'
maintenance_marker="$state_root/$release.maintenance"
maintenance_owned() {
    [[ -f "$maintenance_marker" && -e storage/framework/down ]] || return 1
    [[ $(cat "$maintenance_marker") == "$release $(sha256sum storage/framework/down | cut -d' ' -f1)" ]]
}
if [[ -e "$maintenance_marker" ]]; then
    [[ $(cat "$maintenance_marker") =~ ^$release\ [0-9a-f]{64}$ ]] || fail 'Unexpected maintenance ownership marker.'
fi
if [[ -e storage/framework/down ]]; then
    maintenance_owned || fail 'Maintenance does not match this deployment ownership record.'
fi
export GIT_TERMINAL_PROMPT=0
git fetch origin master
[[ $(git rev-parse origin/master) == "$release" ]] || fail 'origin/master differs from the reviewed release.'
git merge-base --is-ancestor "$expected_base" "$release" || fail 'Release has an unexpected base.'
paths=$(git diff --name-only "$expected_base" "$release")
[[ -n "$paths" ]] || fail 'The release is empty.'
while IFS= read -r path; do
    case "$path" in
        app/Http/Controllers/Back/DiscountController.php|app/Models/CustomerDiscount.php|app/Models/Front/Catalog/Product.php|app/Services/QuantityDiscounts.php|app/Services/RecordedPrices.php|app/Services/PriceListPublisher.php|database/migrations/2026_11_23_190000_add_everyone_to_customer_discounts.php|resources/views/back/discount/edit.blade.php|resources/views/back/discount/index.blade.php|tests/Feature/QuantityDiscountTest.php|tests/Feature/EveryoneDiscountAdminTest.php|tests/Feature/EveryoneDiscountViewTest.php|tests/Feature/EveryoneDiscountPublicPriceTest.php|tests/JavaScript/everyone-discount.test.cjs|scripts/deploy-everyone-discount-20261123.sh|docs/everyone-discount-2026-11-23.md) ;;
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
# Protect requests from seeing the new column-dependent code before its migration.
if [[ ! -e storage/framework/down ]]; then
    "$php_bin" artisan down --retry=15
    [[ -e storage/framework/down ]] || fail 'Maintenance did not activate.'
    (umask 077; printf '%s %s\n' "$release" "$(sha256sum storage/framework/down | cut -d' ' -f1)" > "$maintenance_marker")
fi
maintenance_owned || fail 'Maintenance ownership changed before activation.'
git merge --ff-only "$release"
"$php_bin" artisan migrate --force --path=database/migrations/2026_11_23_190000_add_everyone_to_customer_discounts.php
"$php_bin" artisan route:clear
"$php_bin" artisan view:cache
"$php_bin" -r '
$checked=false;
register_shutdown_function(function () use (&$checked) { if (!$checked) { fwrite(STDERR,"Everyone-discount release verification did not finish.".PHP_EOL); exit(1); } });
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
// Use a canonical request only for this read-only CLI inspection; keep production settings intact.
$app->afterBootstrapping(Illuminate\Foundation\Bootstrap\SetRequestForConsole::class, function ($app) { $app->instance("request",Illuminate\Http\Request::create("https://www.top-tim.com/")); });
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (["discounts.store","discounts.update","products.bulk.destroy","actions.bulk.destroy","products.lowest-price.update"] as $name) {
    $route=Illuminate\Support\Facades\Route::getRoutes()->getByName($name);
    $methods=in_array($name,["discounts.update","products.lowest-price.update"],true) ? ["PATCH"] : ["POST"];
    if (!$route || $route->methods() !== $methods) { fwrite(STDERR,"Missing protected route: ".$name.PHP_EOL); exit(1); }
    echo $name,": ",implode(",",$methods),PHP_EOL;
}
$migration="2026_11_23_190000_add_everyone_to_customer_discounts";
if (!Illuminate\Support\Facades\Schema::hasColumn("customer_discounts","applies_to_everyone") || !Illuminate\Support\Facades\DB::table("migrations")->where("migration",$migration)->exists()) { fwrite(STDERR,"Everyone-discount migration was not applied.".PHP_EOL); exit(1); }
echo "Everyone-discount schema ready.",PHP_EOL;
$checked=true;
'
[[ $(git rev-parse HEAD) == "$release" && $(protected_hash) == "$protected_now" ]] || fail 'Release or protected settings changed.'
check_dirty
maintenance_owned || fail 'Maintenance ownership changed before restoring service.'
"$php_bin" artisan up
[[ ! -e storage/framework/down ]] || fail 'Maintenance did not end.'
rm -f "$maintenance_marker"
printf 'Activated %s; existing discount targets preserved; everyone option ready.\n' "$release"
