<?php

namespace App\Filament\Pages;

use App\Services\Naver\ClovaSpeechService;
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

class ClovaSpeechTester extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Clova Speech';
    protected static ?string $title = 'Clova Speech Test';

    public array $data = [];
    
    #[Locked]
    public ?string $result = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-microphone';
    }

    protected function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'result' => $this->result,
        ]);
    }

    public function getView(): string
    {
        return 'filament.pages.clova-speech-tester';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Audio Transcription')->schema([
                FileUpload::make('audio')->label('Upload Audio File')
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/m4a'])
                    ->maxSize(20480)
                    ->directory('temp/speech')
                    ->disk(config('filesystems.public_disk'))
                    ->preserveFilenames()
                    ->visibility('public')
                    ->hint('Upload an audio file (mp3, wav, m4a) for speech-to-text'),
            ]),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('transcribe')->label('Transcribe Audio')->icon('heroicon-o-microphone')->action('transcribeAudio'),
        ];
    }

    public function transcribeAudio(): void
    {
        $path = $this->data['audio'] ?? null;
        
        if (!$path) {
            Notification::make()->title('Upload an audio file')->danger()->send();
            return;
        }
        
        // Filament FileUpload returns an array, get the first element
        if (is_array($path)) {
            $path = $path[0] ?? null;
        }
        
        if (!$path) {
            Notification::make()->title('No audio file found')->danger()->send();
            return;
        }
        
        try {
            $service = app(ClovaSpeechService::class);
            $fullPath = Storage::disk(config('filesystems.public_disk'))->path($path);
            $audio = new \Illuminate\Http\UploadedFile($fullPath, basename($path));
            $result = $service->speechToText($audio);
            $this->result = json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            Notification::make()->title('Transcription Success!')->success()->send();
        } catch (\Exception $e) {
            $this->result = json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
        }
    }
}
