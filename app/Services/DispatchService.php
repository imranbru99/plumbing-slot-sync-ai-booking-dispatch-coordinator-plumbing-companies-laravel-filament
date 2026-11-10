<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\JobTicket;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Support\ModuleGate;
use Illuminate\Support\Str;

class DispatchService
{
    public function offerAppointment(ServiceRequest $request, Technician $technician, string $startsAt, string $endsAt, ?int $userId = null): Appointment
    {
        $appointment = Appointment::create([
            'slot_code' => 'SLT-'.Str::upper(Str::random(8)),
            'service_request_id' => $request->id,
            'technician_id' => $technician->id,
            'customer_id' => $request->customer_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'status' => 'offered',
            'calendar_synced' => ModuleGate::enabled('google_calendar_sync'),
            'external_calendar_id' => ModuleGate::enabled('google_calendar_sync') ? 'gcal_'.Str::random(10) : null,
            'dispatch_notes' => 'Auto-offered via PlumbingSlotSync dispatch coordinator.',
        ]);

        $request->update([
            'status' => 'slot_offered',
            'assigned_technician_id' => $technician->id,
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'dispatch.slot_offered',
            'subject_type' => Appointment::class,
            'subject_id' => $appointment->id,
            'summary' => "Offered {$appointment->slot_code} to customer for {$request->ticket_no}",
        ]);

        return $appointment;
    }

    public function confirmAppointment(Appointment $appointment, ?int $userId = null): JobTicket
    {
        $appointment->update(['status' => 'confirmed']);
        $appointment->serviceRequest?->update(['status' => 'dispatched']);

        $ticket = JobTicket::create([
            'job_no' => 'JOB-'.Str::upper(Str::random(8)),
            'appointment_id' => $appointment->id,
            'technician_id' => $appointment->technician_id,
            'service_request_id' => $appointment->service_request_id,
            'status' => 'scheduled',
            'checklist' => [
                'Verify customer access',
                'Confirm fixture / shutoff locations',
                'Safety / licensing check',
                'Complete service notes',
                'Capture photos',
            ],
        ]);

        AuditLog::create([
            'user_id' => $userId,
            'action' => 'dispatch.confirmed',
            'subject_type' => JobTicket::class,
            'subject_id' => $ticket->id,
            'summary' => "Confirmed {$appointment->slot_code} → created {$ticket->job_no}",
        ]);

        return $ticket;
    }
}
