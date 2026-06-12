<?php

namespace App\Http\Controllers\Api;

use App\Enums\DraftSource;
use App\Enums\DraftStatus;
use App\Exceptions\InvalidDraftStateException;
use App\Http\Controllers\Controller;
use App\Jobs\GeneratePostVariantsJob;
use App\Jobs\PublishTelegramPostJob;
use App\Models\PostDraft;
use App\Models\PostVariant;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Drafts\DraftStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DraftController extends Controller
{
    public function __construct(
        private readonly CurrentUserResolver $users,
        private readonly DraftStateService $state,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);

        $drafts = PostDraft::query()
            ->whereBelongsTo($user)
            ->with(['variants', 'selectedVariant', 'publishLogs', 'telegramChannel'])
            ->latest()
            ->paginate((int) $request->query('per_page', 20));

        return response()->json($drafts);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->users->resolve($request);

        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'max:10240'],
        ]);

        $imagePath = $request->file('image')?->store("web/{$user->id}");

        $draft = PostDraft::query()->create([
            'user_id' => $user->id,
            'telegram_channel_id' => $user->telegramChannel?->id,
            'prompt' => $validated['prompt'],
            'image_path' => $imagePath,
            'source' => DraftSource::Web,
            'status' => DraftStatus::Draft,
        ]);

        $this->state->markGenerating($draft);
        GeneratePostVariantsJob::dispatch($draft->id);

        return response()->json(
            $draft->refresh()->load(['variants', 'telegramChannel']),
            202,
        );
    }

    public function show(Request $request, PostDraft $draft): JsonResponse
    {
        $user = $this->users->resolve($request);
        $this->authorizeDraft($draft, $user->id);

        return response()->json($draft->load(['variants', 'selectedVariant', 'publishLogs', 'telegramChannel']));
    }

    public function selectVariant(Request $request, PostDraft $draft): JsonResponse
    {
        $user = $this->users->resolve($request);
        $this->authorizeDraft($draft, $user->id);

        $validated = $request->validate([
            'variant_id' => ['required', 'integer'],
        ]);

        $variant = PostVariant::query()->whereKey($validated['variant_id'])->firstOrFail();

        try {
            $this->state->selectVariant($draft, $variant);
        } catch (InvalidDraftStateException $exception) {
            throw ValidationException::withMessages(['draft' => $exception->getMessage()]);
        }

        return response()->json($draft->refresh()->load(['variants', 'selectedVariant']));
    }

    public function approve(Request $request, PostDraft $draft): JsonResponse
    {
        $user = $this->users->resolve($request);
        $this->authorizeDraft($draft, $user->id);

        try {
            $this->state->approve($draft);
        } catch (InvalidDraftStateException $exception) {
            throw ValidationException::withMessages(['draft' => $exception->getMessage()]);
        }

        return response()->json($draft->refresh()->load(['variants', 'selectedVariant']));
    }

    public function publish(Request $request, PostDraft $draft): JsonResponse
    {
        $user = $this->users->resolve($request);
        $this->authorizeDraft($draft, $user->id);

        try {
            $this->state->assertCanPublish($draft);
        } catch (InvalidDraftStateException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        PublishTelegramPostJob::dispatch($draft->id);

        return response()->json([
            'message' => 'Publishing queued.',
            'draft' => $draft->refresh(),
        ], 202);
    }

    public function cancel(Request $request, PostDraft $draft): JsonResponse
    {
        $user = $this->users->resolve($request);
        $this->authorizeDraft($draft, $user->id);

        try {
            $this->state->cancel($draft);
        } catch (InvalidDraftStateException $exception) {
            throw ValidationException::withMessages(['draft' => $exception->getMessage()]);
        }

        return response()->json($draft->refresh());
    }

    private function authorizeDraft(PostDraft $draft, int $userId): void
    {
        abort_unless((int) $draft->user_id === $userId, 404);
    }
}
