<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=14 : Nombre de jours de sauvegardes à conserver}';

    protected $description = 'Sauvegarde la base de données dans storage/app/backups et purge les anciennes copies';

    /**
     * Préfixe des fichiers de sauvegarde. Il portait le nom du premier client,
     * ce qui donnait des « coleah-2026-10-27.sql » chez tous les suivants.
     */
    private const PREFIXE = 'sauvegarde-';

    public function handle(): int
    {
        $dossier = storage_path('app/backups');

        if (! is_dir($dossier) && ! mkdir($dossier, 0755, true) && ! is_dir($dossier)) {
            $this->error("Impossible de créer {$dossier}.");

            return self::FAILURE;
        }

        $fichier = $dossier.'/'.self::PREFIXE.Carbon::now()->format('Y-m-d_His').'.sql';

        if (! $this->dumper($fichier)) {
            return self::FAILURE;
        }

        // Un dump vide ou tronqué est pire qu'une absence de sauvegarde : il donne
        // l'illusion d'être protégé. On refuse de le garder.
        if (! is_file($fichier) || filesize($fichier) < 1024) {
            $this->error('Le fichier produit est vide ou tronqué — sauvegarde abandonnée.');
            @unlink($fichier);
            Log::error('[Sauvegarde] Dump vide ou tronqué', ['fichier' => $fichier]);

            return self::FAILURE;
        }

        $taille = filesize($fichier);
        $fichier = $this->compresser($fichier);

        $this->info(sprintf(
            'Sauvegarde écrite : %s (%s)',
            basename($fichier),
            $this->lisible(filesize($fichier))
        ));

        if ($taille !== filesize($fichier)) {
            $this->line(sprintf('  compressée depuis %s', $this->lisible($taille)));
        }

        $this->purger($dossier, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    /**
     * Exécute mysqldump.
     *
     * Les identifiants passent par un fichier d'options temporaire et jamais en
     * argument : sur un serveur partagé, « --password=... » est lisible par tout
     * le monde dans la liste des processus.
     */
    private function dumper(string $destination): bool
    {
        $binaire = env('DB_DUMP_BINARY', 'mysqldump');
        $config = config('database.connections.'.config('database.default'));

        $options = tempnam(sys_get_temp_dir(), 'dbk');
        file_put_contents($options, sprintf(
            "[client]\nhost=%s\nport=%s\nuser=%s\npassword=\"%s\"\n",
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 3306,
            $config['username'] ?? '',
            str_replace('"', '\"', (string) ($config['password'] ?? ''))
        ));
        @chmod($options, 0600);

        $commande = sprintf(
            '%s --defaults-extra-file=%s --single-transaction --quick --routines --events %s > %s 2>&1',
            escapeshellarg($binaire),
            escapeshellarg($options),
            escapeshellarg($config['database']),
            escapeshellarg($destination)
        );

        exec($commande, $sortie, $code);
        @unlink($options);

        if ($code !== 0) {
            $this->error('mysqldump a échoué (code '.$code.').');
            // La sortie d'erreur atterrit dans le fichier : on la remonte puis on
            // supprime le fichier, qui ne contient qu'un message d'erreur.
            $detail = is_file($destination) ? trim((string) file_get_contents($destination)) : implode("\n", $sortie);
            if ($detail !== '') {
                $this->line('  '.mb_substr($detail, 0, 300));
            }
            @unlink($destination);
            Log::error('[Sauvegarde] mysqldump en échec', ['code' => $code, 'detail' => mb_substr($detail, 0, 500)]);

            return false;
        }

        return true;
    }

    /**
     * Compresse le dump si l'extension zlib est disponible, sinon le laisse tel quel.
     */
    private function compresser(string $fichier): string
    {
        if (! function_exists('gzopen')) {
            return $fichier;
        }

        $archive = $fichier.'.gz';
        $source = fopen($fichier, 'rb');
        $cible = gzopen($archive, 'wb9');

        if (! $source || ! $cible) {
            return $fichier;
        }

        while (! feof($source)) {
            gzwrite($cible, (string) fread($source, 262144));
        }

        fclose($source);
        gzclose($cible);
        @unlink($fichier);

        return $archive;
    }

    private function purger(string $dossier, int $jours): void
    {
        $limite = Carbon::now()->subDays(max(0, $jours))->getTimestamp();
        $supprimes = 0;

        // Les deux motifs : « coleah- » est celui des installations créées avant
        // que le nom d'un client cesse d'être écrit dans le code. Sans lui, leurs
        // anciennes sauvegardes ne seraient plus jamais purgées et le disque se
        // remplirait en silence.
        $anciennes = array_merge(
            glob($dossier.'/'.self::PREFIXE.'*.sql*') ?: [],
            glob($dossier.'/coleah-*.sql*') ?: [],
        );

        foreach ($anciennes as $ancien) {
            if (filemtime($ancien) < $limite) {
                @unlink($ancien);
                $supprimes++;
            }
        }

        if ($supprimes > 0) {
            $this->line(sprintf('%d ancienne(s) sauvegarde(s) supprimée(s).', $supprimes));
        }
    }

    private function lisible(int $octets): string
    {
        foreach (['o', 'Ko', 'Mo', 'Go'] as $unite) {
            if ($octets < 1024) {
                return round($octets, 1).' '.$unite;
            }
            $octets /= 1024;
        }

        return round($octets, 1).' To';
    }
}
