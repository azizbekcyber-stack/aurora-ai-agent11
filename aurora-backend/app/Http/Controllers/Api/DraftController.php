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
use Illuminate\Support\Facades\Storage;
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
            'image_data' => ['nullable', 'string'],
            'image_mime_type' => ['required_with:image_data', 'string', 'in:image/jpeg,image/png,image/webp'],
        ]);

        $imagePath = $this->storeImage($request, $user->id);

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

    private function storeImage(Request $request, int $userId): ?string
    {
        if ($request->filled('image_data')) {
            return $this->storeBase64Image(
                (string) $request->input('image_data'),
                (string) $request->input('image_mime_type'),
                $userId,
            );
        }

        return $request->file('image')?->store("web/{$userId}");
    }

    private function storeBase64Image(string $imageData, string $mimeType, int $userId): string
    {
        $contents = base64_decode($imageData, true);

        if ($contents === false) {
            throw ValidationException::withMessages([
                'image' => 'The image data is not valid.',
            ]);
        }

        $maxBytes = (int) config('aurora.max_image_bytes', 10 * 1024 * 1024);

        if (strlen($contents) > $maxBytes) {
            throw ValidationException::withMessages([
                'image' => 'The image may not be greater than 10 MB.',
            ]);
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: '';

        if ($detectedMime !== $mimeType) {
            throw ValidationException::withMessages([
                'image' => 'The image type is not supported.',
            ]);
        }

        $extension = match ($mimeType) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $path = sprintf('web/%s/%s.%s', $userId, uniqid('image_', true), $extension);
        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
