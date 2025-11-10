<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class NaverServicesTester extends Page
{
    protected static ?string $navigationLabel = 'NAVER Services';
    protected static ?string $title = 'NAVER API Testing';
    
    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-beaker';
    }
    
    public function render(): \Illuminate\Contracts\View\View
    {
        return view('filament.pages.naver-services-tester');
    }
}
