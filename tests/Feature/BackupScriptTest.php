<?php

use Symfony\Component\Process\Process;

/**
 * Führt scripts/backup-db.sh gegen ein Fake-`docker` aus (kein echter MySQL-Container).
 *
 * @return array{process: Process, dir: string, log: string}
 */
function runBackup(string $mode, ?string $dir = null): array
{
    $dir ??= sys_get_temp_dir().'/backup-test-'.bin2hex(random_bytes(4));
    @mkdir($dir.'/bin', 0777, true);
    $log = $dir.'/docker-args.log';

    file_put_contents($dir.'/bin/docker', <<<'SH'
#!/bin/bash
echo "$@" >> "$FAKE_DOCKER_LOG"
case "$FAKE_DOCKER_MODE" in
  ok)    echo "INSERT INTO t VALUES (1);"; echo "-- Dump completed on 2026-08-01" ;;
  empty) ;;
  trunc) echo "INSERT INTO t VALUES (1);" ;;
  fail)  echo "boom" >&2; exit 1 ;;
esac
SH);
    chmod($dir.'/bin/docker', 0755);

    $process = new Process(['bash', base_path('scripts/backup-db.sh')], null, [
        'PATH' => $dir.'/bin:'.getenv('PATH'),
        'BACKUP_DIR' => $dir.'/backups',
        'FAKE_DOCKER_MODE' => $mode,
        'FAKE_DOCKER_LOG' => $log,
    ]);
    $process->run();

    return ['process' => $process, 'dir' => $dir, 'log' => $log];
}

function backupFiles(string $dir): array
{
    return array_values(array_filter(scandir($dir.'/backups') ?: [], fn ($f) => $f !== '.' && $f !== '..'));
}

test('erfolgreiches Backup legt eine gültige, nur für den Besitzer lesbare .sql.gz an', function () {
    $r = runBackup('ok');

    expect($r['process']->isSuccessful())->toBeTrue($r['process']->getErrorOutput());

    $files = backupFiles($r['dir']);
    expect($files)->toHaveCount(1)->and($files[0])->toMatch('/^laravel_.*\.sql\.gz$/');

    $path = $r['dir'].'/backups/'.$files[0];
    expect(shell_exec('gzip -t '.escapeshellarg($path).' 2>&1'))->toBeNull()
        ->and(substr(sprintf('%o', fileperms($path)), -3))->toBe('600');
});

test('das Datenbank-Passwort steht nicht in den docker-Argumenten', function () {
    $r = runBackup('ok');

    $args = file_get_contents($r['log']);

    expect($args)->not->toMatch('/-p["\']?\$/')->not->toContain('-p"')
        ->and($args)->toContain('MYSQL_PWD');
});

test('ein fehlgeschlagener Dump hinterlässt keine Datei und endet mit Fehler', function () {
    $r = runBackup('fail');

    expect($r['process']->isSuccessful())->toBeFalse()
        ->and(backupFiles($r['dir']))->toBe([]);
});

test('ein leerer oder abgeschnittener Dump wird nicht als Backup behalten', function (string $mode) {
    $r = runBackup($mode);

    expect($r['process']->isSuccessful())->toBeFalse()
        ->and(backupFiles($r['dir']))->toBe([]);
})->with(['empty', 'trunc']);

test('alte Backups werden nur nach einem erfolgreichen Lauf gelöscht', function () {
    $dir = sys_get_temp_dir().'/backup-test-'.bin2hex(random_bytes(4));
    @mkdir($dir.'/backups', 0777, true);
    $alt = $dir.'/backups/laravel_2020-01-01_030000.sql.gz';
    file_put_contents($alt, 'alt');
    touch($alt, strtotime('-200 days'));

    $fehl = runBackup('fail', $dir);
    expect(file_exists($alt))->toBeTrue('Altes Backup darf bei fehlgeschlagenem Lauf nicht gelöscht werden');

    $ok = runBackup('ok', $dir);
    expect($ok['process']->isSuccessful())->toBeTrue()
        ->and(file_exists($alt))->toBeFalse();
});
