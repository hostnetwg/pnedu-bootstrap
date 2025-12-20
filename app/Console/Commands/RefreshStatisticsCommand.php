<?php

namespace App\Console\Commands;

use App\Services\StatisticsService;
use Illuminate\Console\Command;

class RefreshStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Odświeża statystyki wyświetlane na stronie głównej (czyści cache i generuje nowe wartości)';

    /**
     * Execute the console command.
     */
    public function handle(StatisticsService $statisticsService)
    {
        $this->info('Odświeżanie statystyk...');

        try {
            $statistics = $statisticsService->refreshStatistics();

            $this->info('Statystyki zostały odświeżone:');
            $this->table(
                ['Statystyka', 'Wartość'],
                [
                    ['Przeszkolonych nauczycieli', number_format($statistics['trained_teachers'], 0, ',', ' ')],
                    ['Szkoleń rocznie', number_format($statistics['courses_this_year'], 0, ',', ' ')],
                    ['Średnia ocena', number_format($statistics['average_rating'], 1, ',', '.')],
                    ['Wskaźnik poleceń (NPS)', number_format($statistics['nps'], 1, ',', '.')],
                ]
            );

            $this->info('✅ Statystyki zostały pomyślnie zaktualizowane i zapisane w cache.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Błąd podczas odświeżania statystyk: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

