<?php

namespace App\Filament\Pages;

use App\Services\Naver\SearchTrendService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;

class SearchTrendTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Search Trend';
    protected static ?string $title = 'Search Trend Test';

    public array $data = [];
    
    #[Locked]
    public ?string $result = null;

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'time_unit' => 'date',
            'device' => '',
            'keywords' => [['text' => '']],
        ]);
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-chart-bar';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.search-trend-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Search Trend Analysis')->schema([
                DatePicker::make('start_date')->label('Start Date')->required(),
                DatePicker::make('end_date')->label('End Date')->required(),
                Select::make('time_unit')->label('Time Unit')->options([
                    'date' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly',
                ])->default('date')->required(),
                Select::make('device')->label('Device')->options([
                    '' => 'All Devices', 'pc' => 'PC Only', 'mo' => 'Mobile Only',
                ])->default(''),
                Repeater::make('keywords')->label('Keywords (max 5)')->schema([
                    TextInput::make('text')->label('Keyword')->required(),
                ])->defaultItems(1)->minItems(1)->maxItems(5),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('getTrends')->label('Get Trends')->icon('heroicon-o-chart-bar')->action('getTrends'),
        ];
    }

    public function getTrends(): void
    {
        if (empty($this->data['keywords'])) {
            Notification::make()->title('Add at least one keyword')->danger()->send();
            return;
        }
        try {
            $service = app(SearchTrendService::class);
            $keywords = array_column($this->data['keywords'], 'text');
            $result = $service->getKeywordTrends(
                $keywords,
                $this->data['start_date'],
                $this->data['end_date'],
                $this->data['time_unit'] ?? 'date',
                $this->data['device'] ?? ''
            );
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Trend Data Retrieved!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
