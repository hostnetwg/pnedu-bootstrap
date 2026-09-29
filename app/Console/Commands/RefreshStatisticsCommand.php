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
                    ['Przeszkolonych nauczycieli', $this->formatStat($statistics['trained_teachers'] ?? null, 0)],
                    ['Szkoleń rocznie', $this->formatStat($statistics['courses_this_year'] ?? null, 0)],
                    ['Średnia ocena', $this->formatStat($statistics['average_rating'] ?? null, 1)],
                    ['Wskaźnik poleceń (NPS)', $this->formatStat($statistics['nps'] ?? null, 1)],
                ]
            );

            $this->info('✅ Statystyki zostały pomyślnie zaktualizowane i zapisane w cache.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Błąd podczas odświeżania statystyk: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function formatStat(mixed $value, int $decimals): string
    {
        if (! is_numeric($value)) {
            return 'brak danych';
        }

        return number_format((float) $value, $decimals, ',', $decimals === 0 ? ' ' : '.');
    }
}
