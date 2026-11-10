<?php

namespace App\Services;

use App\Models\AiDraft;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\RescheduleRequest;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Support\ModuleGate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AiSchedulingService
{
    public function classifyUrgency(ServiceRequest $request): string
    {
        $text = Str::lower(($request->service_type ?? '').' '.($request->description ?? ''));

        if (Str::contains($text, ['burst', 'flood', 'no water', 'sewage', 'gas', 'leak', 'emergency', 'overflow'])) {
            return 'emergency';
        }

        if (Str::contains($text, ['clog', 'slow drain', 'water heater', 'water_heater', 'lukewarm', 'backing up', 'noisy'])) {
            return 'high';
        }

        return $request->priority === 'high' ? 'high' : 'normal';
    }

    public function suggestSlots(ServiceRequest $request, int $limit = 3): Collection
    {
        $urgency = $this->classifyUrgency($request);
        $hoursAhead = match ($urgency) {
            'emergency' => 4,
            'high' => 24,
            default => 48,
        };

        $technicians = Technician::query()
            ->where('status', 'available')
            ->when($request->zip, function ($q) use ($request) {
                $q->where(function ($inner) use ($request) {
                    $inner->whereNull('service_zips')
                        ->orWhere('service_zips', 'like', '%'.$request->zip.'%');
                });
            })
            ->orderByDesc('rating')
            ->limit(5)
            ->get();

        $suggestions = collect();
        $cursor = Carbon::now()->addHours($hoursAhead)->minute(0)->second(0);

        foreach ($technicians as $tech) {
            for ($i = 0; $i < 2; $i++) {
                $start = $cursor->copy()->addHours($i * 3);
                if ($start->hour < 8) {
                    $start->setTime(8, 0);
                }
                if ($start->hour > 17) {
                    $start->addDay()->setTime(9, 0);
                }

                $suggestions->push([
                    'technician_id' => $tech->id,
                    'technician_name' => $tech->name,
                    'starts_at' => $start->toDateTimeString(),
                    'ends_at' => $start->copy()->addHours(2)->toDateTimeString(),
                    'score' => round(70 + ($tech->rating * 5) + ($urgency === 'emergency' ? 10 : 0), 1),
                    'rationale' => sprintf(
                        'Match %s (%s) near ZIP %s with capacity %d/day. Urgency=%s.',
                        $tech->name,
                        $tech->specialty,
                        $request->zip ?? 'n/a',
                        $tech->daily_capacity,
                        $urgency
                    ),
                ]);
            }
        }

        return $suggestions->sortByDesc('score')->take($limit)->values();
    }

    public function draftSlotOffer(ServiceRequest $request, ?int $userId = null): AiDraft
    {
        $slots = $this->suggestSlots($request);
        $lines = $slots->map(function ($slot, $i) {
            return sprintf(
                "%d) %s with %s (score %s) — %s",
                $i + 1,
                $slot['starts_at'],
                $slot['technician_name'],
                $slot['score'],
                $slot['rationale']
            );
        })->implode("\n");

        $content = "AI drafted appointment offer for {$request->ticket_no} ({$request->service_type}):\n\n{$lines}\n\nRecommended next action: present top slot to customer after human approval.";

        $draft = AiDraft::create([
            'draft_type' => 'slot_offer',
            'subject_type' => ServiceRequest::class,
            'subject_id' => $request->id,
            'title' => "Slot offer draft · {$request->ticket_no}",
            'content' => $content,
            'status' => ModuleGate::enabled('human_approval') ? 'pending_approval' : 'auto_ready',
            'meta' => ['slots' => $slots->all(), 'urgency' => $this->classifyUrgency($request)],
            'created_by' => $userId,
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'ai.draft.slot_offer',
            'subject_type' => AiDraft::class,
            'subject_id' => $draft->id,
            'summary' => "AI created slot offer draft for {$request->ticket_no}",
            'meta' => ['service_request_id' => $request->id],
        ]);

        return $draft;
    }

    public function draftReschedule(Appointment $appointment, Carbon $proposedStart, ?string $reason = null, ?int $userId = null): array
    {
        $proposedEnd = $proposedStart->copy()->addHours(2);
        $conflict = Appointment::query()
            ->where('technician_id', $appointment->technician_id)
            ->where('id', '!=', $appointment->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($proposedStart, $proposedEnd) {
                $q->whereBetween('starts_at', [$proposedStart, $proposedEnd])
                    ->orWhereBetween('ends_at', [$proposedStart, $proposedEnd]);
            })
            ->exists();

        $suggestion = $conflict
            ? 'Conflict detected on technician calendar. Suggest next open window +2 hours or alternate tech with matching specialty.'
            : 'No hard conflict found. Safe to offer this window pending customer confirmation and ops approval.';

        $reschedule = RescheduleRequest::create([
            'appointment_id' => $appointment->id,
            'original_starts_at' => $appointment->starts_at,
            'proposed_starts_at' => $proposedStart,
            'proposed_ends_at' => $proposedEnd,
            'reason' => $reason,
            'status' => 'pending',
            'ai_suggestion' => $suggestion,
            'requested_by' => $userId,
        ]);

        $draft = AiDraft::create([
            'draft_type' => 'reschedule_message',
            'subject_type' => RescheduleRequest::class,
            'subject_id' => $reschedule->id,
            'title' => "Reschedule draft · {$appointment->slot_code}",
            'content' => "Proposed move of {$appointment->slot_code} from {$appointment->starts_at} to {$proposedStart}.\nAI note: {$suggestion}",
            'status' => 'pending_approval',
            'meta' => ['conflict' => $conflict],
            'created_by' => $userId,
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'ai.draft.reschedule',
            'subject_type' => RescheduleRequest::class,
            'subject_id' => $reschedule->id,
            'summary' => "AI drafted reschedule for {$appointment->slot_code}",
        ]);

        return compact('reschedule', 'draft');
    }

    public function approveDraft(AiDraft $draft, int $userId): AiDraft
    {
        $draft->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'ai.draft.approved',
            'subject_type' => AiDraft::class,
            'subject_id' => $draft->id,
            'summary' => "Approved AI draft: {$draft->title}",
        ]);

        return $draft->fresh();
    }
}
