<?php

namespace App\Filament\Pages;

use App\Services\Naver\PapagoService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;

class PapagoTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Papago Translation';
    protected static ?string $title = 'Papago Translation Test';
    
    public array $data = [];
    
    #[Locked]
    public ?string $result = null;
    protected $listeners = ['refreshForm' => '$refresh'];

    public function mount(): void
    {
        $this->form->fill();
    }
    
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-language';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.papago-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Text Translation')->schema([
                Textarea::make('text')->label('Text to Translate')->rows(3),
                Select::make('source_lang')->label('Source Language')->options([
                    'auto' => 'Auto Detect', 'ko' => 'Korean', 'en' => 'English',
                    'ja' => 'Japanese', 'zh-CN' => 'Chinese (Simplified)',
                    'zh-TW' => 'Chinese (Traditional)', 'es' => 'Spanish',
                    'fr' => 'French', 'de' => 'German', 'ru' => 'Russian',
                ])->default('auto'),
                Select::make('target_lang')->label('Target Language')->options([
                    'ko' => 'Korean', 'en' => 'English', 'ja' => 'Japanese',
                    'zh-CN' => 'Chinese (Simplified)', 'zh-TW' => 'Chinese (Traditional)',
                    'es' => 'Spanish', 'fr' => 'French', 'de' => 'German', 'ru' => 'Russian',
                ])->default('en')->required(),
            ])->columns(3),

            Section::make('Image Translation')->schema([
                FileUpload::make('image')
                    ->image()
                    ->maxSize(10240)
                    ->directory('temp/papago')
                    ->disk(config('filesystems.public_disk'))
                    ->preserveFilenames()
                    ->visibility('public'),
                Select::make('image_target')->label('Target Language')->options([
                    'ko' => 'Korean', 'en' => 'English', 'ja' => 'Japanese',
                ])->default('en'),
            ])->columns(2),

            Section::make('Language Detection')->schema([
                Textarea::make('detect_text')->label('Text to Detect Language')->rows(2),
            ]),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('translate')->label('Translate Text')->icon('heroicon-o-language')->action('translateText'),
            Action::make('translateImage')->label('Translate Image')->icon('heroicon-o-photo')->action('translateImage'),
            Action::make('detectLanguage')->label('Detect Language')->icon('heroicon-o-magnifying-glass')->action('detectLanguage'),
        ];
    }

    public function translateText(): void
    {
        if (!($text = $this->data['text'] ?? null)) {
            Notification::make()->title('Enter text')->danger()->send();
            return;
        }
        try {
            $service = app(PapagoService::class);
            $source = $this->data['source_lang'] === 'auto' ? null : $this->data['source_lang'];
            $result = $service->translate($text, $this->data['target_lang'] ?? 'en', $source);
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Translation Success!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function translateImage(): void
    {
        $path = $this->data['image'] ?? null;
        
        if (!$path) {
            Notification::make()->title('Upload image')->danger()->send();
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
            $service = app(PapagoService::class);
            $fullPath = Storage::disk(config('filesystems.public_disk'))->path($path);
            $image = new \Illuminate\Http\UploadedFile($fullPath, basename($path));
            $result = $service->translateImage($image, $this->data['image_target'] ?? 'en');
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Image Translation Success!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function detectLanguage(): void
    {
        if (!($text = $this->data['detect_text'] ?? null)) {
            Notification::make()->title('Enter text')->danger()->send();
            return;
        }
        try {
            $service = app(PapagoService::class);
            $result = $service->detectLanguage($text);
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Language Detected!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
