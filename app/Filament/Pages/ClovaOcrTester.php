<?php

namespace App\Filament\Pages;

use App\Services\Naver\ClovaOcrService;
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

class ClovaOcrTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Clova OCR';
    protected static ?string $title = 'Clova OCR Test';

    public array $data = [];
    
    #[Locked]
    public ?string $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.clova-ocr-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Extract Text from Image')->schema([
                FileUpload::make('image')->label('Upload Image')
                    ->image()
                    ->maxSize(10240)
                    ->directory('temp/ocr')
                    ->disk(config('filesystems.public_disk'))
                    ->preserveFilenames()
                    ->visibility('public')
                    ->hint('Upload an image containing text to extract'),
            ]),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('extract')->label('Extract Text')->icon('heroicon-o-document-text')->action('extractText'),
        ];
    }

    public function extractText(): void
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
            $service = app(ClovaOcrService::class);
            $fullPath = Storage::disk(config('filesystems.public_disk'))->path($path);
            $image = new \Illuminate\Http\UploadedFile($fullPath, basename($path));
            $result = $service->extractText($image);
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('OCR Success!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
