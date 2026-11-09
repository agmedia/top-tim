#!/usr/bin/env bash
# Git-only deployment of the reviewed Laravel/security upgrade. Run stage before activate.
# Bootstrap from the fetched commit without changing the live checkout:
# git show "$release:scripts/deploy-security-hardening.sh" | bash -s -- stage "$release"
set -Eeuo pipefail
umask 077

phase=${1:-}
release=${2:-}
[[ "$phase" == stage || "$phase" == activate ]] || { echo 'Usage: deploy-security-hardening.sh stage|activate FULL_COMMIT' >&2; exit 2; }
[[ "$release" =~ ^[0-9a-f]{40}$ ]] || { echo 'A full, reviewed commit identifier is required.' >&2; exit 2; }
checkout=${TOP_TIM_CHECKOUT:-/home/toptimco/public_html}
state_root=${TOP_TIM_DEPLOY_STATE:-$HOME/.cache/top-tim-deploy}
php_bin=${TOP_TIM_PHP:-php}
expected_base=08df89703a82ce3512ae983a4f07adaec606e572
composer_sha=6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c
maintenance_started=0

fail() {
    echo "Deployment stopped: $*" >&2
    if [[ "$maintenance_started" == 1 ]]; then
        echo 'Maintenance remains enabled. Repair forward; no automatic rollback was attempted.' >&2
    fi
    exit 1
}
on_error() {
    echo "Deployment failed during $phase at line $2 (exit $1)." >&2
    if [[ "$maintenance_started" == 1 ]]; then
        echo 'Maintenance remains enabled. Inspect the failure and repair forward; no rollback or automatic artisan up was attempted.' >&2
    else
        echo 'No live release activation was completed.' >&2
    fi
    exit "$1"
}
trap 'on_error "$?" "$LINENO"' ERR
for command in git curl sha256sum flock "$php_bin"; do command -v "$command" >/dev/null || fail "Missing command: $command"; done
cd "$checkout"
checkout=$(pwd -P)
[[ $(git rev-parse --show-toplevel) == "$checkout" ]] || fail 'The checkout root is not the expected directory.'
[[ $(git branch --show-current) == master ]] || fail 'Production must remain on master.'
[[ $(git remote get-url origin) == git@github.com:agmedia/top-tim.git ]] || fail 'The private production SSH origin is not configured.'
[[ $(git config --get core.sshCommand) == *top-tim-github-readonly* ]] || fail 'The separate production read-only Git key is not selected.'
[[ $(git config --get core.sshCommand) == *StrictHostKeyChecking=yes* ]] || fail 'Strict GitHub host verification is required.'
git diff --cached --quiet || fail 'Production has staged changes.'
changed_paths=$(git diff --name-only)
while IFS= read -r path; do
    case "$path" in config/mail.php|public/.htaccess|'') ;; *) fail "Unexpected production change: $path" ;; esac
done <<< "$changed_paths"
[[ -r .env && -r config/mail.php && -r public/.htaccess ]] || fail 'Protected production configuration is missing.'
protected_hash() { sha256sum .env config/mail.php public/.htaccess | sha256sum | cut -d' ' -f1; }
protected_now=$(protected_hash)

mkdir -p "$state_root"
state_root=$(cd "$state_root" && pwd -P)
case "$state_root/" in "$checkout/"*) fail 'Deployment tools and dependencies must stay outside the web checkout.' ;; esac
exec 9>"$state_root/deploy.lock"
flock -n 9 || fail 'Another deployment is running.'
release_state="$state_root/$release"
worktree="$release_state/source"
composer="$state_root/composer-2.11.1.phar"
export COMPOSER_HOME="$state_root/composer-home"
export COMPOSER_CACHE_DIR="$state_root/composer-cache"
export GIT_TERMINAL_PROMPT=0

# Fetch and require the exact reviewed master, never a moving branch as the merge target.
git fetch origin master
[[ $(git rev-parse origin/master) == "$release" ]] || fail 'origin/master differs from the reviewed release.'
git merge-base --is-ancestor "$expected_base" "$release" || fail 'The release does not descend from the audited production base.'
[[ -z $(git diff --name-only "$expected_base" "$release" -- .env config/mail.php public/.htaccess) ]] || fail 'The release would change protected production configuration.'
media_paths=$(git diff --name-only "$expected_base" "$release" -- public/media)
while IFS= read -r path; do
    case "$path" in public/media/.htaccess|'') ;; *) fail "The release would change user media: $path" ;; esac
done <<< "$media_paths"

if [[ "$phase" == stage ]]; then
    [[ $(git rev-parse HEAD) == "$expected_base" ]] || fail 'The live release changed since the audit.'
    [[ ! -f storage/framework/down ]] || fail 'The application is already in maintenance.'
    # Official release checksum: https://getcomposer.org/download/ (version 2.11.1).
    official_sha=$(curl --proto '=https' --proto-redir '=https' --tlsv1.2 -fsSL --connect-timeout 15 --max-time 60 \
        https://getcomposer.org/download/2.11.1/composer.phar.sha256sum | awk 'NR==1 {print $1}')
    [[ "$official_sha" == "$composer_sha" ]] || fail 'Official Composer checksum differs from the reviewed pin.'
    if [[ ! -f "$composer" ]]; then
        curl --proto '=https' --proto-redir '=https' --tlsv1.2 -fsSL --connect-timeout 15 --max-time 180 \
            https://getcomposer.org/download/2.11.1/composer.phar -o "$composer.part.$$"
        [[ $(sha256sum "$composer.part.$$" | cut -d' ' -f1) == "$composer_sha" ]] || fail 'Composer download verification failed.'
        mv "$composer.part.$$" "$composer"
    fi
    [[ $(sha256sum "$composer" | cut -d' ' -f1) == "$composer_sha" ]] || fail 'Pinned Composer file was changed.'
    mkdir -p "$release_state"
    if [[ ! -d "$worktree" ]]; then git worktree add --detach "$worktree" "$release"; fi
    [[ $(git -C "$worktree" rev-parse HEAD) == "$release" ]] || fail 'The staged worktree has the wrong release.'
    [[ ! -e "$worktree/.env" ]] || fail 'The staging worktree must not contain production credentials.'
    (
        cd "$worktree"
        "$php_bin" "$composer" install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress --optimize-autoloader
        "$php_bin" "$composer" check-platform-reqs --no-dev
        git diff --exit-code -- composer.json composer.lock
    )
    [[ $(protected_hash) == "$protected_now" && $(git rev-parse HEAD) == "$expected_base" ]] || fail 'Production changed during staging.'
    printf '%s\n%s\n%s\n' "$expected_base" "$protected_now" "$(git show "$release:composer.lock" | sha256sum | cut -d' ' -f1)" > "$release_state/ready"
    echo "Staged $release. The live checkout and vendor directory are unchanged."
    exit 0
fi

[[ -f "$release_state/ready" && -d "$worktree/vendor" ]] || fail 'Run stage successfully before activate.'
{ IFS= read -r staged_base; IFS= read -r staged_protected; IFS= read -r staged_lock; } < "$release_state/ready"
[[ "$staged_base" == "$expected_base" && "$staged_protected" == "$protected_now" ]] || fail 'Protected configuration changed after staging.'
[[ $(git show "$release:composer.lock" | sha256sum | cut -d' ' -f1) == "$staged_lock" ]] || fail 'The staged lock file differs from the release.'
[[ $(sha256sum "$composer" | cut -d' ' -f1) == "$composer_sha" ]] || fail 'Pinned Composer file was changed.'
current=$(git rev-parse HEAD)
[[ "$current" == "$expected_base" || "$current" == "$release" ]] || fail 'The live release changed after staging.'
if [[ -e public/media/.htaccess ]] && ! git ls-files --error-unmatch public/media/.htaccess >/dev/null 2>&1; then
    fail 'An existing media .htaccess must be reviewed before Git can add its protection.'
fi

if [[ "$current" == "$expected_base" ]]; then
    [[ ! -f storage/framework/down ]] || fail 'The application was already in maintenance; inspect before activating.'
    # Laravel 8 prerenders before vendor changes. public/index.php loads this before autoload.
    "$php_bin" artisan down --render=errors::503 --retry=60
    maintenance_started=1
    [[ -s storage/framework/down && -s storage/framework/maintenance.php ]] || fail 'Prerendered maintenance was not created.'
else
    # An interrupted activation can only resume forward with the same staged commit and maintenance active.
    [[ -s storage/framework/down && -s storage/framework/maintenance.php ]] || fail 'This release is already active; no recovery is needed.'
    maintenance_started=1
fi
umask 022
sleep 2
# Only Git changes application source. Dependency installation follows the committed lock file.
git merge --ff-only "$release"
"$php_bin" "$composer" install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress --optimize-autoloader
# The standard Laravel Composer script clears obsolete generated config/package/service manifests before discovery.
"$php_bin" "$composer" run-script post-autoload-dump --no-dev --no-interaction
"$php_bin" artisan migrate --force \
    --path=database/migrations/2026_11_09_150100_add_expires_at_to_personal_access_tokens.php \
    --path=database/migrations/2026_11_09_160000_add_deleted_at_to_users_table.php
"$php_bin" artisan optimize:clear
"$php_bin" artisan config:cache
"$php_bin" artisan view:cache
"$php_bin" artisan queue:restart
# Livewire and frontend assets are tracked in Git; do not publish or rebuild code on this server.
[[ $(protected_hash) == "$protected_now" ]] || fail 'Protected production configuration changed.'
[[ $(git rev-parse HEAD) == "$release" ]] || fail 'The activated commit does not match the reviewed release.'
git diff --cached --quiet
changed_paths=$(git diff --name-only)
while IFS= read -r path; do
    case "$path" in config/mail.php|public/.htaccess|'') ;; *) fail "A deployment step rewrote tracked code: $path" ;; esac
done <<< "$changed_paths"
"$php_bin" "$composer" check-platform-reqs --no-dev
"$php_bin" artisan up
maintenance_started=0
printf 'Activated %s; protected settings and user media preserved.\n' "$release"
