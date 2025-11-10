<?php

namespace App\Filament\Pages;

use App\Services\Naver\LocalSearchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\Locked;

class LocalSearchTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Local Search';
    protected static ?string $title = 'Local Search Test';

    public array $data = [];
    
    #[Locked]
    public ?string $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map-pin';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.local-search-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Place Search')->schema([
                TextInput::make('query')->label('Search Query')->placeholder('Cafe, Restaurant, etc.'),
                TextInput::make('display')->label('Display Count')->numeric()->default(5)->minValue(1)->maxValue(25),
            ])->columns(2),

            Section::make('Nearby Search')->schema([
                TextInput::make('x')->label('Longitude (X)')->numeric()->step('any')->placeholder('127.027619'),
                TextInput::make('y')->label('Latitude (Y)')->numeric()->step('any')->placeholder('37.497952'),
                TextInput::make('radius')->label('Radius (meters)')->numeric()->default(1000)->minValue(1)->maxValue(10000),
                TextInput::make('nearby_query')->label('Query (optional)')->placeholder('Coffee'),
            ])->columns(2),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('search')->label('Place Search')->icon('heroicon-o-magnifying-glass')->action('searchPlaces'),
            Action::make('nearby')->label('Nearby Search')->icon('heroicon-o-map-pin')->action('searchNearby'),
        ];
    }

    public function searchPlaces(): void
    {
        if (!($query = $this->data['query'] ?? null)) {
            Notification::make()->title('Enter search query')->danger()->send();
            return;
        }
        try {
            $service = app(LocalSearchService::class);
            $result = $service->search($query, (int)($this->data['display'] ?? 5));
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Search Success!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function searchNearby(): void
    {
        if (!isset($this->data['x']) || !isset($this->data['y'])) {
            Notification::make()->title('Enter coordinates (X, Y)')->danger()->send();
            return;
        }
        try {
            $service = app(LocalSearchService::class);
            $result = $service->searchNearby(
                (float)$this->data['x'],
                (float)$this->data['y'],
                (int)($this->data['radius'] ?? 1000),
                $this->data['nearby_query'] ?? null
            );
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Nearby Search Success!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
