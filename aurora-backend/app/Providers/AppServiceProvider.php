<?php

namespace App\Providers;

use App\Contracts\PostGenerator;
use App\Services\AI\FakePostGenerationService;
use App\Services\AI\GeminiPostGenerationService;
use App\Services\AI\ImageContextService;
use App\Services\AI\VariantNormalizer;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PostGenerator::class, function ($app): PostGenerator {
            if (filled(config('services.gemini.key'))) {
                return new GeminiPostGenerationService(
                    $app->make(VariantNormalizer::class),
                    $app->make(ImageContextService::class),
                );
            }

            return $app->make(FakePostGenerationService::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
