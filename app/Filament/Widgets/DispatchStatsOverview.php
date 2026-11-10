<?php

namespace App\Filament\Widgets;

use App\Models\AiDraft;
use App\Models\Appointment;
use App\Models\JobTicket;
use App\Models\RescheduleRequest;
use App\Models\ServiceRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DispatchStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Open Intake', ServiceRequest::whereIn('status', ['intake', 'slot_offered'])->count())
                ->description('Service requests awaiting dispatch')
                ->icon('heroicon-o-inbox'),
            Stat::make('Offered Slots', Appointment::where('status', 'offered')->count())
                ->description('Appointments waiting on customer')
                ->icon('heroicon-o-calendar'),
            Stat::make('Active Jobs', JobTicket::whereIn('status', ['scheduled', 'en_route', 'on_site'])->count())
                ->description('Field tickets in motion')
                ->icon('heroicon-o-wrench-screwdriver'),
            Stat::make('AI Pending', AiDraft::where('status', 'pending_approval')->count())
                ->description('Drafts needing human approval')
                ->icon('heroicon-o-sparkles'),
            Stat::make('Reschedules', RescheduleRequest::where('status', 'pending')->count())
                ->description('Calendar moves in review')
                ->icon('heroicon-o-arrow-path'),
        ];
    }
}
