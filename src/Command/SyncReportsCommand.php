<?php

namespace App\Command;

use App\Service\ReportManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-reports',
    description: 'Synchronise les rapports HTML avec la base de données',
)]
class SyncReportsCommand extends Command
{
    public function __construct(
        private ReportManager $reportManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronisation des rapports SEO');

        $io->note('Scanning du dossier public/reports/ ...');

        $result = $this->reportManager->scanAndSyncReports();

        $io->success('Synchronisation terminée !');

        // Afficher les résultats
        $io->section('Résultats');
        $io->table(
            ['Statistique', 'Valeur'],
            [
                ['Fichiers trouvés', $result['total']],
                ['Nouveaux rapports', count($result['synced'])],
                ['Erreurs', count($result['errors'])],
            ]
        );

        if (!empty($result['synced'])) {
            $io->section('Rapports synchronisés');
            $io->listing($result['synced']);
        }

        if (!empty($result['errors'])) {
            $io->section('Erreurs');
            $io->listing($result['errors']);
            return Command::FAILURE;
        }

        $io->note([
            'Pour ajouter un nouveau rapport :',
            '1. Placez le fichier HTML dans public/reports/',
            '2. Nommez-le selon la convention : 2025-11.html ou rapport-2025-11.html',
            '3. Exécutez cette commande',
        ]);

        return Command::SUCCESS;
    }
}
