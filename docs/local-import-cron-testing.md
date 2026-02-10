# Local Import Cron Testing

## 1) Prepare local SQLite DB

```bash
rm -f /tmp/top_tim_test.sqlite
touch /tmp/top_tim_test.sqlite
DB_CONNECTION=sqlite DB_DATABASE=/tmp/top_tim_test.sqlite php artisan migrate --force
```

## 2) Run app + scheduler

Terminal A:

```bash
DB_CONNECTION=sqlite DB_DATABASE=/tmp/top_tim_test.sqlite php artisan serve
```

Terminal B:

```bash
DB_CONNECTION=sqlite DB_DATABASE=/tmp/top_tim_test.sqlite php artisan schedule:work
```

`schedule:work` triggers `process:queued-full-excel-imports` every minute.

## 3) Test flow from admin UI

1. Upload images (ZIP or multiple images) in API settings page.
2. Upload full Excel (`Import Full Excel` button).
3. Request should return immediately (`job queued`), no HTTP timeout.
4. After up to 1 minute, check `CRON Reports`.

## 4) Optional manual processor run

```bash
DB_CONNECTION=sqlite DB_DATABASE=/tmp/top_tim_test.sqlite php artisan process:queued-full-excel-imports --limit=1
```

