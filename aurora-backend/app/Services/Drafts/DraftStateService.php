<?php

namespace App\Services\Drafts;

use App\Enums\DraftStatus;
use App\Exceptions\InvalidDraftStateException;
use App\Models\PostDraft;
use App\Models\PostVariant;

class DraftStateService
{
    /**
     * @var array<string, array<int, DraftStatus>>
     */
    private array $allowed = [
        'draft' => [DraftStatus::Generating],
        'generating' => [DraftStatus::Generated, DraftStatus::Failed],
        'generated' => [DraftStatus::Selected, DraftStatus::Cancelled],
        'selected' => [DraftStatus::Approved, DraftStatus::Cancelled],
        'approved' => [DraftStatus::Publishing, DraftStatus::Cancelled],
        'publishing' => [DraftStatus::Published, DraftStatus::Failed],
        'failed' => [DraftStatus::Approved],
    ];

    public function transition(PostDraft $draft, DraftStatus $to): PostDraft
    {
        $from = $draft->status instanceof DraftStatus
            ? $draft->status
            : DraftStatus::from($draft->status);

        if ($from === $to) {
            return $draft;
        }

        $allowedTargets = $this->allowed[$from->value] ?? [];

        if (! in_array($to, $allowedTargets, true)) {
            throw new InvalidDraftStateException(sprintf(
                'Cannot move draft %s from "%s" to "%s".',
                $draft->id,
                $from->value,
                $to->value,
            ));
        }

        $draft->forceFill(['status' => $to])->save();

        return $draft->refresh();
    }

    public function markGenerating(PostDraft $draft): PostDraft
    {
        return $this->transition($draft, DraftStatus::Generating);
    }

    public function markGenerated(PostDraft $draft): PostDraft
    {
        return $this->transition($draft, DraftStatus::Generated);
    }

    public function markPublishing(PostDraft $draft): PostDraft
    {
        $this->assertCanPublish($draft);

        return $this->transition($draft, DraftStatus::Publishing);
    }

    public function markPublished(PostDraft $draft): PostDraft
    {
        return $this->transition($draft, DraftStatus::Published);
    }

    public function markFailed(PostDraft $draft): PostDraft
    {
        $from = $draft->status instanceof DraftStatus
            ? $draft->status
            : DraftStatus::from($draft->status);

        if (! in_array($from, [DraftStatus::Generating, DraftStatus::Publishing], true)) {
            $draft->forceFill(['status' => DraftStatus::Failed])->save();

            return $draft->refresh();
        }

        return $this->transition($draft, DraftStatus::Failed);
    }

    public function selectVariant(PostDraft $draft, PostVariant $variant): PostDraft
    {
        if ((int) $variant->post_draft_id !== (int) $draft->id) {
            throw new InvalidDraftStateException('Selected variant does not belong to this draft.');
        }

        if ($draft->status === DraftStatus::Generated) {
            $this->transition($draft, DraftStatus::Selected);
        } elseif ($draft->status !== DraftStatus::Selected) {
            throw new InvalidDraftStateException('Only generated drafts can have a variant selected.');
        }

        $draft->forceFill(['selected_variant_id' => $variant->id])->save();

        return $draft->refresh();
    }

    public function approve(PostDraft $draft): PostDraft
    {
        if (! $draft->selected_variant_id) {
            throw new InvalidDraftStateException('Draft cannot be approved without a selected variant.');
        }

        return $this->transition($draft, DraftStatus::Approved);
    }

    public function cancel(PostDraft $draft): PostDraft
    {
        return $this->transition($draft, DraftStatus::Cancelled);
    }

    public function regenerate(PostDraft $draft): PostDraft
    {
        if (! in_array($draft->status, [DraftStatus::Generated, DraftStatus::Selected, DraftStatus::Failed], true)) {
            throw new InvalidDraftStateException('Only generated, selected, or failed drafts can be regenerated.');
        }

        $draft->variants()->delete();
        $draft->forceFill([
            'selected_variant_id' => null,
            'status' => DraftStatus::Generating,
        ])->save();

        return $draft->refresh();
    }

    public function assertCanPublish(PostDraft $draft): void
    {
        if ($draft->status !== DraftStatus::Approved) {
            throw new InvalidDraftStateException('This draft cannot be published because it has not been approved yet.');
        }

        if (! $draft->selected_variant_id) {
            throw new InvalidDraftStateException('Draft cannot be published without a selected variant.');
        }
    }
}
