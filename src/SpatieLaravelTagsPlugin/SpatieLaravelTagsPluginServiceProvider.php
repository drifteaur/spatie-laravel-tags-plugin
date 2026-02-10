<?php

namespace Filament\SpatieLaravelTagsPlugin;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SpatieLaravelTagsPluginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('spatie-tags-plugin')
            ->hasViews('spatie-tags-plugin');
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            AlpineComponent::make('spatie-tags-input', __DIR__ . '/../../dist/components/spatie-tags-input.js'),
        ], 'filament/spatie-laravel-tags-plugin');
    }

    protected function getPackageBaseDir(): string
    {
        return dirname(__DIR__);
    }
}
