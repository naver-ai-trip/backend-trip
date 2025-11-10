<?php

namespace App\Filament\Pages;

use App\Services\Naver\NaverMapsService;
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

class MapsTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Maps Tester';
    protected static ?string $title = 'NAVER Maps API Test';

    public array $data = [];
    
    #[Locked]
    public ?string $result = null;
    
    #[Locked]
    public ?array $mapData = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-map';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
            'mapData' => $this->mapData,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.maps-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Geocoding')->schema([
                TextInput::make('geocode_query')
                    ->label('Address or Place Name')
                    ->placeholder('강남역, 서울시청, etc.')
                    ->columnSpan(2),
            ])->columns(2),

            Section::make('Reverse Geocoding')->schema([
                TextInput::make('reverse_lat')
                    ->label('Latitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.4999940'),
                TextInput::make('reverse_lng')
                    ->label('Longitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.0357270'),
            ])->columns(2),

            Section::make('Directions 5 (Simple Route)')->schema([
                TextInput::make('dir5_start_lat')
                    ->label('Start Latitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.4999940'),
                TextInput::make('dir5_start_lng')
                    ->label('Start Longitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.0357270'),
                TextInput::make('dir5_goal_lat')
                    ->label('Goal Latitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.5678901'),
                TextInput::make('dir5_goal_lng')
                    ->label('Goal Longitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.1234567'),
                Select::make('dir5_option')
                    ->label('Route Option')
                    ->options([
                        'trafast' => 'Traffic-aware Fast (Default)',
                        'traoptimal' => 'Traffic-aware Optimal',
                        'tracomfort' => 'Traffic-aware Comfort',
                        'traavoidtoll' => 'Avoid Tolls',
                        'traavoidcaronly' => 'Avoid Car-only Roads',
                    ])
                    ->default('traoptimal'),
            ])->columns(3),

            Section::make('Directions 15 (Multi-waypoint Route)')->schema([
                TextInput::make('dir15_start_lat')
                    ->label('Start Latitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.4999940'),
                TextInput::make('dir15_start_lng')
                    ->label('Start Longitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.0357270'),
                TextInput::make('dir15_waypoint1_lat')
                    ->label('Waypoint 1 Lat')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.5123456'),
                TextInput::make('dir15_waypoint1_lng')
                    ->label('Waypoint 1 Lng')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.0555555'),
                TextInput::make('dir15_waypoint2_lat')
                    ->label('Waypoint 2 Lat (optional)')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.5345678'),
                TextInput::make('dir15_waypoint2_lng')
                    ->label('Waypoint 2 Lng (optional)')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.0777777'),
                TextInput::make('dir15_goal_lat')
                    ->label('Goal Latitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('37.5678901'),
                TextInput::make('dir15_goal_lng')
                    ->label('Goal Longitude')
                    ->numeric()
                    ->step('any')
                    ->placeholder('127.1234567'),
                Select::make('dir15_option')
                    ->label('Route Option')
                    ->options([
                        'trafast' => 'Traffic-aware Fast',
                        'traoptimal' => 'Traffic-aware Optimal (Default)',
                        'tracomfort' => 'Traffic-aware Comfort',
                        'traavoidtoll' => 'Avoid Tolls',
                        'traavoidcaronly' => 'Avoid Car-only Roads',
                    ])
                    ->default('traoptimal'),
            ])->columns(3),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('geocode')
                ->label('Geocode')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->action('testGeocode'),
            Action::make('reverseGeocode')
                ->label('Reverse Geocode')
                ->icon('heroicon-o-globe-alt')
                ->color('info')
                ->action('testReverseGeocode'),
            Action::make('directions5')
                ->label('Directions 5')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->action('testDirections5'),
            Action::make('directions15')
                ->label('Directions 15')
                ->icon('heroicon-o-arrows-right-left')
                ->color('success')
                ->action('testDirections15'),
        ];
    }

    public function testGeocode(): void
    {
        $query = $this->data['geocode_query'] ?? null;
        
        if (!$query) {
            Notification::make()
                ->title('Enter address or place name')
                ->danger()
                ->send();
            return;
        }

        try {
            $service = app(NaverMapsService::class);
            $result = $service->geocode($query);
            
            if ($result) {
                $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $this->mapData = [
                    'center' => ['lat' => $result['latitude'], 'lng' => $result['longitude']],
                    'markers' => [
                        ['lat' => $result['latitude'], 'lng' => $result['longitude'], 'label' => $query],
                    ],
                ];
                Notification::make()->title('Geocode Success!')->success()->send();
            } else {
                $this->result = json_encode(['error' => 'No results found'], JSON_PRETTY_PRINT);
                Notification::make()->title('No results found')->warning()->send();
            }
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function testReverseGeocode(): void
    {
        $lat = $this->data['reverse_lat'] ?? null;
        $lng = $this->data['reverse_lng'] ?? null;

        if (!$lat || !$lng) {
            Notification::make()
                ->title('Enter both latitude and longitude')
                ->danger()
                ->send();
            return;
        }

        try {
            $service = app(NaverMapsService::class);
            $result = $service->reverseGeocode((float)$lat, (float)$lng);
            
            if ($result) {
                $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $this->mapData = [
                    'center' => ['lat' => (float)$lat, 'lng' => (float)$lng],
                    'markers' => [
                        ['lat' => (float)$lat, 'lng' => (float)$lng, 'label' => $result['roadAddress']],
                    ],
                ];
                Notification::make()->title('Reverse Geocode Success!')->success()->send();
            } else {
                $this->result = json_encode(['error' => 'No results found'], JSON_PRETTY_PRINT);
                Notification::make()->title('No results found')->warning()->send();
            }
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function testDirections5(): void
    {
        $startLat = $this->data['dir5_start_lat'] ?? null;
        $startLng = $this->data['dir5_start_lng'] ?? null;
        $goalLat = $this->data['dir5_goal_lat'] ?? null;
        $goalLng = $this->data['dir5_goal_lng'] ?? null;

        if (!$startLat || !$startLng || !$goalLat || !$goalLng) {
            Notification::make()
                ->title('Enter all coordinates (start + goal)')
                ->danger()
                ->send();
            return;
        }

        try {
            $service = app(NaverMapsService::class);
            $option = $this->data['dir5_option'] ?? 'traoptimal';
            
            $result = $service->getDirections5(
                (float)$startLat,
                (float)$startLng,
                (float)$goalLat,
                (float)$goalLng,
                ['option' => $option]
            );
            
            if ($result) {
                $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
                // Prepare map data with route
                $this->mapData = [
                    'center' => ['lat' => (float)$startLat, 'lng' => (float)$startLng],
                    'markers' => [
                        ['lat' => (float)$startLat, 'lng' => (float)$startLng, 'label' => 'Start'],
                        ['lat' => (float)$goalLat, 'lng' => (float)$goalLng, 'label' => 'Goal'],
                    ],
                    'path' => $result['path'] ?? [],
                    'distance' => $result['distance'],
                    'duration' => $result['duration'],
                ];
                
                Notification::make()
                    ->title('Directions Success!')
                    ->body(sprintf('Distance: %d m, Duration: %d ms', $result['distance'], $result['duration']))
                    ->success()
                    ->send();
            } else {
                $this->result = json_encode(['error' => 'No route found'], JSON_PRETTY_PRINT);
                Notification::make()->title('No route found')->warning()->send();
            }
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function testDirections15(): void
    {
        $startLat = $this->data['dir15_start_lat'] ?? null;
        $startLng = $this->data['dir15_start_lng'] ?? null;
        $goalLat = $this->data['dir15_goal_lat'] ?? null;
        $goalLng = $this->data['dir15_goal_lng'] ?? null;

        if (!$startLat || !$startLng || !$goalLat || !$goalLng) {
            Notification::make()
                ->title('Enter all required coordinates (start + goal)')
                ->danger()
                ->send();
            return;
        }

        try {
            $service = app(NaverMapsService::class);
            $option = $this->data['dir15_option'] ?? 'traoptimal';
            
            // Collect waypoints if provided
            $waypoints = [];
            if (!empty($this->data['dir15_waypoint1_lat']) && !empty($this->data['dir15_waypoint1_lng'])) {
                $waypoints[] = [
                    'lat' => (float)$this->data['dir15_waypoint1_lat'],
                    'lng' => (float)$this->data['dir15_waypoint1_lng'],
                ];
            }
            if (!empty($this->data['dir15_waypoint2_lat']) && !empty($this->data['dir15_waypoint2_lng'])) {
                $waypoints[] = [
                    'lat' => (float)$this->data['dir15_waypoint2_lat'],
                    'lng' => (float)$this->data['dir15_waypoint2_lng'],
                ];
            }
            
            $result = $service->getDirections15(
                (float)$startLat,
                (float)$startLng,
                (float)$goalLat,
                (float)$goalLng,
                $waypoints,
                ['option' => $option]
            );
            
            if ($result) {
                $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                
                // Prepare map data with route and waypoints
                $markers = [
                    ['lat' => (float)$startLat, 'lng' => (float)$startLng, 'label' => 'Start'],
                ];
                
                foreach ($waypoints as $i => $wp) {
                    $markers[] = ['lat' => $wp['lat'], 'lng' => $wp['lng'], 'label' => 'WP' . ($i + 1)];
                }
                
                $markers[] = ['lat' => (float)$goalLat, 'lng' => (float)$goalLng, 'label' => 'Goal'];
                
                $this->mapData = [
                    'center' => ['lat' => (float)$startLat, 'lng' => (float)$startLng],
                    'markers' => $markers,
                    'path' => $result['path'] ?? [],
                    'distance' => $result['distance'],
                    'duration' => $result['duration'],
                ];
                
                Notification::make()
                    ->title('Directions 15 Success!')
                    ->body(sprintf(
                        'Distance: %d m, Duration: %d ms, Waypoints: %d',
                        $result['distance'],
                        $result['duration'],
                        count($waypoints)
                    ))
                    ->success()
                    ->send();
            } else {
                $this->result = json_encode(['error' => 'No route found'], JSON_PRETTY_PRINT);
                Notification::make()->title('No route found')->warning()->send();
            }
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
