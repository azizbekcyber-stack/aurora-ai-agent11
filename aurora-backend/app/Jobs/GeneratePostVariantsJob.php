<?php

namespace App\Jobs;

use App\Contracts\PostGenerator;
use App\Enums\AiGenerationStatus;
use App\Enums\DraftSource;
use App\Enums\DraftStatus;
use App\Models\PostDraft;
use App\Services\Drafts\DraftStateService;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GeneratePostVariantsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $draftId)
    {
    }

    public function handle(
        PostGenerator $generator,
        DraftStateService $state,
        TelegramBotService $bot,
    ): void {
        $draft = PostDraft::query()->with('user.brandProfile')->findOrFail($this->draftId);

        try {
            if ($draft->status === DraftStatus::Draft) {
                $state->markGenerating($draft);
            }

            $result = $generator->generate($draft->refresh());
            $draft->variants()->delete();

            foreach ($result->variants as $variant) {
                $draft->variants()->create($variant);
            }

            $draft->aiGenerationLogs()->create([
                'provider' => $result->provider,
                'model' => $result->model,
                'request_payload' => $result->requestPayload,
                'response_payload' => $result->responsePayload,
                'status' => AiGenerationStatus::Success,
            ]);

            $state->markGenerated($draft->refresh());

            if ($draft->source === DraftSource::Telegram) {
                $bot->sendGeneratedVariants($draft->refresh());
            }
        } catch (Throwable $exception) {
            $draft->aiGenerationLogs()->create([
                'provider' => 'openai',
                'model' => (string) config('services.openai.model_text', 'gpt-5-mini'),
                'status' => AiGenerationStatus::Failed,
                'error_message' => $exception->getMessage(),
            ]);

            $state->markFailed($draft->refresh());

            if ($draft->source === DraftSource::Telegram) {
                try {
                    $bot->sendMessage(
                        $draft->user->telegramAccount?->telegram_user_id,
                        'Could not generate post variants. Please try again.',
                    );
                } catch (Throwable) {
                    //
                }
            }
        }
    }
}
