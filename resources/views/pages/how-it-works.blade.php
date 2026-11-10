@extends('layouts.marketing')

@section('title', 'How PlumbingSlotSync Works — Intake to Dispatch Audit Loop')

@section('content')
<section class="wrap section">
    <div class="eyebrow">MVP workflow</div>
    <h1>1 intake trigger → draft → approval → audit</h1>
    <p class="lede">The core automation loop designed for plumbing companies who need speed without losing control.</p>

    <div class="steps" style="margin-top:2rem;">
        <div class="step">
            <h3>Intake trigger</h3>
            <p>A service request arrives from phone, web form, Jobber, or ServiceTitan. Priority and preferred windows are captured.</p>
        </div>
        <div class="step">
            <h3>AI draft / action</h3>
            <p>PlumbingSlotSync classifies urgency, scores technicians, and drafts a slot offer or reschedule proposal.</p>
        </div>
        <div class="step">
            <h3>Human approval</h3>
            <p>Owner or dispatcher reviews the AI draft, adjusts if needed, and approves customer-facing action.</p>
        </div>
        <div class="step">
            <h3>Dispatch + audit log</h3>
            <p>Confirmed slots become job tickets. Every step is written to the compliance audit trail.</p>
        </div>
    </div>
</section>
@endsection
