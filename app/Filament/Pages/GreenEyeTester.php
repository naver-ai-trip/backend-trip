<?php

namespace App\Filament\Pages;

use App\Services\Naver\GreenEyeService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;

class GreenEyeTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Green-Eye';
    protected static ?string $title = 'Green-Eye Test';

    public array $data = [];
    
    #[Locked]
    public ?string $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-shield-check';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.green-eye-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Content Moderation')->schema([
                FileUpload::make('image')->label('Upload Image to Check')
                    ->image()
                    ->maxSize(10240)
                    ->directory('temp/greeneye')
                    ->disk(config('filesystems.public_disk'))
                    ->preserveFilenames()
                    ->visibility('public')
                    ->hint('Upload an image to check for adult/violent content'),
            ]),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('check')->label('Check Safety')->icon('heroicon-o-shield-check')->action('checkSafety'),
        ];
    }

    public function checkSafety(): void
    {
        $path = $this->data['image'] ?? null;
        
        if (!$path) {
            Notification::make()->title('Upload an image')->danger()->send();
            return;
        }
        
        // Filament FileUpload returns an array, get the first element
        if (is_array($path)) {
            $path = $path[0] ?? null;
        }
        
        if (!$path) {
            Notification::make()->title('No image file found')->danger()->send();
            return;
        }
        
        try {
            $service = app(GreenEyeService::class);
            $url = Storage::disk(config('filesystems.public_disk'))->url($path);
            $result = $service->checkImageSafety($url);
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Safety Check Complete!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
