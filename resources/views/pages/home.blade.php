@extends('layouts.marketing')

@section('title', 'PlumbingSlotSync — AI Booking & Dispatch Coordinator for Plumbing Companies')
@section('meta_description', 'Offer available plumbing appointments, reschedule across calendars, dispatch field technicians, and approve AI scheduling drafts with audit-ready compliance.')

@section('content')
<section class="wrap hero">
    <div>
        <div class="eyebrow">Plumbing Companies · Home & Field Services</div>
        <h1>PlumbingSlotSync</h1>
        <p class="lede">AI booking &amp; dispatch coordinator that offers available appointments, handles rescheduling across calendars, and keeps service requests, technicians, and job tickets in sync — with human approval and audit logs.</p>
        <div class="cta-row">
            <a class="btn btn-primary" href="/admin">Launch dispatch board</a>
            <a class="btn btn-ghost" href="{{ route('features') }}">Explore all features</a>
        </div>
        <p style="margin-top:1.25rem;color:var(--muted);font-size:0.95rem;">{{ $enabledCount }} modules enabled in this demo · Pilot pricing $199–$799/mo</p>
    </div>
    <div class="panel">
        <h3>Live slot offer preview</h3>
        <div class="slot"><strong>Today 2:00–4:00 PM · Taylor Tech</strong><span>Burst pipe · ZIP 75201 · AI score 94.2</span></div>
        <div class="slot"><strong>Tomorrow 9:00–11:00 AM · Jordan Pipe</strong><span>Leak repair · ZIP 75201 · AI score 91.0</span></div>
        <div class="slot"><strong>Tomorrow 1:00–3:00 PM · Riley Flow</strong><span>Water heater · ZIP 75024 · AI score 88.5</span></div>
    </div>
</section>

<section class="wrap section">
    <h2>Built for plumbing ops leads</h2>
    <p class="lede">Replace spreadsheet chaos and calendar collisions with an intake → AI draft → human approval → dispatch loop.</p>
    <div class="grid-3" style="margin-top:1.5rem;">
        <div class="feature">
            <h3>Offer the right slot</h3>
            <p>Match technician specialty, ZIP coverage, and daily capacity when proposing appointments.</p>
        </div>
        <div class="feature">
            <h3>Reschedule without chaos</h3>
            <p>Detect conflicts across calendars and draft customer-ready reschedule messages for approval.</p>
        </div>
        <div class="feature">
            <h3>Dispatch with proof</h3>
            <p>Confirm slots into job tickets, track attendance, and keep an immutable audit trail.</p>
        </div>
    </div>
</section>

<section class="wrap section">
    <h2>Modules at a glance</h2>
    <div class="grid-2" style="margin-top:1.25rem;">
        @foreach($modules->take(8) as $module)
            <div class="feature">
                <div style="display:flex;justify-content:space-between;gap:0.75rem;align-items:center;margin-bottom:0.4rem;">
                    <h3 style="margin:0;">{{ $module->name }}</h3>
                    <span class="pill {{ $module->enabled ? 'on' : 'off' }}">{{ $module->enabled ? 'ON' : 'OFF' }}</span>
                </div>
                <p>{{ $module->description }}</p>
            </div>
        @endforeach
    </div>
    <div class="cta-row">
        <a class="btn btn-ghost" href="{{ route('modules') }}">View every module</a>
    </div>
</section>
@endsection
