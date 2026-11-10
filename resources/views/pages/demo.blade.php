@extends('layouts.marketing')

@section('title', 'Demo Users — PlumbingSlotSync Admin Login Credentials')

@section('content')
<section class="wrap section">
    <div class="eyebrow">Try the product</div>
    <h1>Demo users</h1>
    <p class="lede">All demo accounts use password <strong>password</strong>. Admin panel: <a href="/admin">/admin</a></p>

    <table class="table" style="margin-top:1.5rem;">
        <thead>
        <tr>
            <th>Role</th>
            <th>Email</th>
            <th>Password</th>
            <th>Access</th>
        </tr>
        </thead>
        <tbody>
        <tr><td>Admin</td><td>admin@plumbingslotsync.test</td><td>password</td><td>Full panel + modules</td></tr>
        <tr><td>Owner</td><td>owner@plumbingslotsync.test</td><td>password</td><td>Ops lead approvals</td></tr>
        <tr><td>Staff / Dispatcher</td><td>staff@plumbingslotsync.test</td><td>password</td><td>Intake, slots, dispatch</td></tr>
        <tr><td>Technician</td><td>tech@plumbingslotsync.test</td><td>password</td><td>Field / job ticket view</td></tr>
        <tr><td>Customer (demo)</td><td>customer@plumbingslotsync.test</td><td>password</td><td>Portal demo identity</td></tr>
        </tbody>
    </table>
</section>
@endsection
