<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use FilesystemIterator;
use RuntimeException;

class RepairMediaPermissions extends Command
{
    protected $signature = 'media:repair-permissions
        {disk : Javni disk, npr. category ili products}
        {ids* : Izričito navedeni pozitivni numerički ID-jevi direktorija}
        {--apply : Primijeni prikazane dozvole; bez ove opcije nema izmjena}';

    protected $description = 'Ciljano provjerava dozvole javnih slika u navedenim ID direktorijima; zadano samo pregled.';

    private const DISKS = ['category', 'sizeguide', 'publisher', 'author', 'products', 'blog', 'recepti', 'page', 'widget', 'qr'];

    public function handle(): int
    {
        try {
            $disk = (string) $this->argument('disk');
            $ids = array_values(array_unique($this->argument('ids')));
            if (!in_array($disk, self::DISKS, true) || !$ids || count($ids) > 50) {
                throw new RuntimeException('Navedite podržani javni media disk i najviše 50 konkretnih ID-jeva.');
            }
            foreach ($ids as $id) {
                if (!preg_match('/\A[1-9][0-9]{0,17}\z/D', (string) $id)) {
                    throw new RuntimeException('ID mora biti pozitivan broj; putanje i skupni odabiri nisu dopušteni.');
                }
            }

            $root = $this->verifiedRoot($disk);
            $plan = [];
            $skipped = 0;
            foreach ($ids as $id) {
                $directory = $root . '/' . $id;
                $stat = $this->verifiedStat($directory, $directory, true);
                $plan[] = $this->entry($directory, (string) $id, $directory, $stat, 0755);
                foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $file) {
                    $name = $file->getFilename();
                    $path = $file->getPathname();
                    $stat = @lstat($path);
                    // Only direct image files are in scope. Never descend into folders or follow links.
                    if (!$stat || ($stat['mode'] & 0170000) !== 0100000 || str_starts_with($name, '.')
                        || !preg_match('/\.(?:jpe?g|png|webp|gif)\z/iD', $name)) {
                        $skipped++;
                        continue;
                    }
                    $stat = $this->verifiedStat($path, $directory, false);
                    $plan[] = $this->entry($path, $id . '/' . $name, $directory, $stat, 0644);
                }
            }

            $changes = array_values(array_filter($plan, fn (array $item) => $item['before'] !== $item['after']));
            $this->table(['Putanja', 'Sada', 'Predloženo'], array_map(fn (array $item) => [
                json_encode($item['relative'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                sprintf('%04o', $item['before']), sprintf('%04o', $item['after']),
            ], $changes));
            $this->line(count($changes) . ' promjena dozvola; ' . $skipped . ' stavki izvan opsega preskočeno.');
            if (!$this->option('apply')) {
                $this->info('Probna provjera: ništa nije promijenjeno. Za primjenu dodati --apply.');
                return self::SUCCESS;
            }

            // Validate the full plan again before any write, including inode identities.
            foreach ($plan as $item) {
                $this->verifyUnchanged($item);
            }
            foreach ($changes as $item) {
                $this->verifyUnchanged($item);
                if (!chmod($item['path'], $item['after'])) {
                    throw new RuntimeException('Dozvole nije moguće promijeniti: ' . $item['relative']);
                }
                clearstatcache(true, $item['path']);
                $stat = $this->verifiedStat($item['path'], $item['directory'], $item['after'] === 0755);
                if (($stat['mode'] & 07777) !== $item['after']) {
                    throw new RuntimeException('Provjera novih dozvola nije uspjela: ' . $item['relative']);
                }
            }
            $this->info('Dozvole primijenjene. Sadržaj datoteka nije mijenjan.');
            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function verifiedRoot(string $disk): string
    {
        $expected = rtrim(public_path('media/img/' . $disk), '/');
        $configured = config('filesystems.disks.' . $disk);
        if (!is_array($configured) || ($configured['driver'] ?? '') !== 'local'
            || rtrim((string) ($configured['root'] ?? ''), '/') !== $expected) {
            throw new RuntimeException('Disk nije očekivani lokalni public/media/img direktorij.');
        }
        foreach ([public_path(), public_path('media'), public_path('media/img'), $expected] as $path) {
            if (is_link($path) || !is_dir($path)) {
                throw new RuntimeException('Media korijen mora postojati i ne smije biti simbolička poveznica.');
            }
        }
        $public = realpath(public_path());
        $root = realpath($expected);
        if (!$public || !$root || $root !== $public . '/media/img/' . $disk) {
            throw new RuntimeException('Media korijen nije unutar očekivanog javnog direktorija.');
        }
        return $root;
    }

    private function verifiedStat(string $path, string $directory, bool $isDirectory): array
    {
        clearstatcache(true, $path);
        $stat = @lstat($path);
        $type = $isDirectory ? 0040000 : 0100000;
        if (!$stat || ($stat['mode'] & 0170000) !== $type || (!$isDirectory && $stat['nlink'] !== 1) || is_link($directory)
            || realpath($directory) !== $directory || realpath($path) !== $path
            || (!$isDirectory && dirname($path) !== $directory)) {
            throw new RuntimeException('Neispravna ili promijenjena putanja; simboličke i tvrde poveznice nisu dopuštene.');
        }
        return $stat;
    }

    private function entry(string $path, string $relative, string $directory, array $stat, int $mode): array
    {
        return ['path' => $path, 'relative' => $relative, 'directory' => $directory,
            'before' => $stat['mode'] & 07777, 'after' => $mode, 'inode' => $stat['ino'], 'device' => $stat['dev']];
    }

    private function verifyUnchanged(array $item): void
    {
        $stat = $this->verifiedStat($item['path'], $item['directory'], $item['after'] === 0755);
        if ($stat['ino'] !== $item['inode'] || $stat['dev'] !== $item['device']
            || ($stat['mode'] & 07777) !== $item['before']) {
            throw new RuntimeException('Datoteka ili dozvole promijenjene su tijekom provjere; prekinuta primjena.');
        }
    }
}
