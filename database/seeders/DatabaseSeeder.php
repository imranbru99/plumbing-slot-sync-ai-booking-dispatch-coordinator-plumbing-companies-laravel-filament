<?php

namespace Database\Seeders;

use App\Models\AttendanceLog;
use App\Models\AuditLog;
use App\Models\CalendarIntegration;
use App\Models\Customer;
use App\Models\FeatureModule;
use App\Models\ServiceRequest;
use App\Models\Technician;
use App\Models\User;
use App\Services\AiSchedulingService;
use App\Services\DispatchService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('modules.definitions', []) as $module) {
            FeatureModule::updateOrCreate(['key' => $module['key']], $module);
        }

        $admin = User::updateOrCreate(
            ['email' => 'admin@plumbingslotsync.test'],
            [
                'name' => 'Alex Admin',
                'password' => Hash::make('password'),
                'role' => User::ROLE_ADMIN,
                'phone' => '555-0100',
                'company' => 'PlumbingSlotSync Demo Plumbing',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $owner = User::updateOrCreate(
            ['email' => 'owner@plumbingslotsync.test'],
            [
                'name' => 'Olivia Owner',
                'password' => Hash::make('password'),
                'role' => User::ROLE_OWNER,
                'phone' => '555-0101',
                'company' => 'FlowRight Plumbing LLC',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $staff = User::updateOrCreate(
            ['email' => 'staff@plumbingslotsync.test'],
            [
                'name' => 'Sam Dispatcher',
                'password' => Hash::make('password'),
                'role' => User::ROLE_STAFF,
                'phone' => '555-0102',
                'company' => 'FlowRight Plumbing LLC',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $techUser = User::updateOrCreate(
            ['email' => 'tech@plumbingslotsync.test'],
            [
                'name' => 'Taylor Tech',
                'password' => Hash::make('password'),
                'role' => User::ROLE_TECHNICIAN,
                'phone' => '555-0103',
                'company' => 'FlowRight Plumbing LLC',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@plumbingslotsync.test'],
            [
                'name' => 'Casey Customer',
                'password' => Hash::make('password'),
                'role' => User::ROLE_CUSTOMER,
                'phone' => '555-0199',
                'company' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $techs = collect([
            ['name' => 'Taylor Tech', 'email' => 'tech@plumbingslotsync.test', 'specialty' => 'drain_cleaning', 'license_number' => 'PLMB-TX-4412', 'service_zips' => '75201,75202,75204', 'user_id' => $techUser->id],
            ['name' => 'Jordan Pipe', 'email' => 'jordan@plumbingslotsync.test', 'specialty' => 'leak_repair', 'license_number' => 'PLMB-TX-5521', 'service_zips' => '75201,75001', 'user_id' => null],
            ['name' => 'Riley Flow', 'email' => 'riley@plumbingslotsync.test', 'specialty' => 'water_heater', 'license_number' => 'PLMB-TX-6630', 'service_zips' => '75204,75024', 'user_id' => null],
        ])->map(function (array $data) {
            return Technician::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['status' => 'available', 'daily_capacity' => 6, 'rating' => 4.8, 'phone' => '555-0200'])
            );
        });

        $customers = collect([
            ['name' => 'Morgan Rivera', 'email' => 'morgan@example.com', 'phone' => '555-1001', 'address' => '120 Maple St', 'city' => 'Dallas', 'state' => 'TX', 'zip' => '75201', 'sms_consent' => true],
            ['name' => 'Avery Chen', 'email' => 'avery@example.com', 'phone' => '555-1002', 'address' => '88 Oak Ave', 'city' => 'Dallas', 'state' => 'TX', 'zip' => '75204', 'sms_consent' => true],
            ['name' => 'Blake Nguyen', 'email' => 'blake@example.com', 'phone' => '555-1003', 'address' => '41 Cedar Ln', 'city' => 'Plano', 'state' => 'TX', 'zip' => '75024', 'sms_consent' => false],
        ])->map(function (array $data) {
            return Customer::updateOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['sms_consent_at' => $data['sms_consent'] ? now()->subDays(10) : null])
            );
        });

        CalendarIntegration::updateOrCreate(
            ['provider' => 'google_calendar'],
            ['label' => 'Ops Shared Calendar', 'enabled' => true, 'config' => ['calendar_id' => 'primary'], 'last_synced_at' => now()->subMinutes(12)]
        );
        CalendarIntegration::updateOrCreate(
            ['provider' => 'jobber'],
            ['label' => 'Jobber Jobs Mirror', 'enabled' => false, 'config' => ['account' => 'demo'], 'last_synced_at' => null]
        );

        $dispatch = app(DispatchService::class);
        $ai = app(AiSchedulingService::class);

        $sr1 = ServiceRequest::updateOrCreate(
            ['ticket_no' => 'SR-1001'],
            [
                'customer_id' => $customers[0]->id,
                'service_type' => 'Burst Pipe Emergency',
                'priority' => 'high',
                'status' => 'intake',
                'source' => 'phone',
                'description' => 'Kitchen supply line burst under sink; water spreading to hardwood floor.',
                'address' => $customers[0]->address,
                'zip' => $customers[0]->zip,
                'preferred_start' => now()->addDay()->setTime(10, 0),
                'preferred_end' => now()->addDay()->setTime(12, 0),
                'created_by' => $staff->id,
            ]
        );

        $sr2 = ServiceRequest::updateOrCreate(
            ['ticket_no' => 'SR-1002'],
            [
                'customer_id' => $customers[1]->id,
                'service_type' => 'Drain Cleaning',
                'priority' => 'normal',
                'status' => 'intake',
                'source' => 'web',
                'description' => 'Main line slow drain; recurring kitchen backup after heavy use.',
                'address' => $customers[1]->address,
                'zip' => $customers[1]->zip,
                'preferred_start' => now()->addDays(2)->setTime(13, 0),
                'preferred_end' => now()->addDays(2)->setTime(15, 0),
                'created_by' => $staff->id,
            ]
        );

        $sr3 = ServiceRequest::updateOrCreate(
            ['ticket_no' => 'SR-1003'],
            [
                'customer_id' => $customers[2]->id,
                'service_type' => 'Water Heater Inspection',
                'priority' => 'normal',
                'status' => 'intake',
                'source' => 'jobber',
                'description' => 'Customer reports lukewarm water and rumbling tank heater.',
                'address' => $customers[2]->address,
                'zip' => $customers[2]->zip,
                'preferred_start' => now()->addDays(3)->setTime(9, 0),
                'preferred_end' => now()->addDays(3)->setTime(11, 0),
                'created_by' => $owner->id,
            ]
        );

        $draft1 = $ai->draftSlotOffer($sr1, $staff->id);
        $slots = $draft1->meta['slots'] ?? [];
        if ($slots) {
            $top = $slots[0];
            $appt1 = $dispatch->offerAppointment(
                $sr1,
                Technician::find($top['technician_id']),
                $top['starts_at'],
                $top['ends_at'],
                $staff->id
            );
            $ai->approveDraft($draft1, $owner->id);
            $dispatch->confirmAppointment($appt1, $owner->id);

            $ai->draftReschedule(
                $appt1,
                now()->addDays(1)->setTime(14, 0),
                'Customer asked for afternoon window',
                $staff->id
            );
        }

        $draft2 = $ai->draftSlotOffer($sr2, $staff->id);
        $slots2 = $draft2->meta['slots'] ?? [];
        if ($slots2) {
            $top2 = $slots2[0];
            $dispatch->offerAppointment(
                $sr2,
                Technician::find($top2['technician_id']),
                $top2['starts_at'],
                $top2['ends_at'],
                $staff->id
            );
        }

        $ai->draftSlotOffer($sr3, $owner->id);

        AttendanceLog::create([
            'technician_id' => $techs[0]->id,
            'event' => 'check_in',
            'logged_at' => now()->subHours(2),
            'source' => 'mobile',
            'geo' => '32.7767,-96.7970',
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'system.seeded',
            'summary' => 'Demo dataset seeded for PlumbingSlotSync booking & dispatch coordinator',
            'meta' => ['users' => 5, 'modules' => FeatureModule::count()],
        ]);
    }
}
