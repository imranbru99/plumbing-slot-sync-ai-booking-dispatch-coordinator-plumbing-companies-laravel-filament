<?php

namespace App\Filament\Resources\Appointments;

use App\Filament\Resources\Appointments\Pages\ManageAppointments;
use App\Models\Appointment;
use App\Services\DispatchService;
use App\Support\ModuleGate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Booking & Dispatch';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return ModuleGate::enabled('slot_offer') || ModuleGate::enabled('dispatch_board');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('slot_code')->default(fn () => 'SLT-'.Str::upper(Str::random(8)))->required(),
            Select::make('service_request_id')->relationship('serviceRequest', 'ticket_no')->searchable()->required(),
            Select::make('customer_id')->relationship('customer', 'name')->searchable()->required(),
            Select::make('technician_id')->relationship('technician', 'name')->searchable(),
            DateTimePicker::make('starts_at')->required(),
            DateTimePicker::make('ends_at')->required(),
            Select::make('status')->options([
                'offered' => 'Offered',
                'confirmed' => 'Confirmed',
                'in_progress' => 'In Progress',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
                'reschedule_pending' => 'Reschedule Pending',
            ])->required(),
            Toggle::make('calendar_synced'),
            Textarea::make('dispatch_notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slot_code')->searchable(),
                TextColumn::make('customer.name'),
                TextColumn::make('technician.name')->label('Tech'),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('calendar_synced')->boolean()->label('Cal'),
            ])
            ->defaultSort('starts_at')
            ->recordActions([
                Action::make('confirm')
                    ->label('Confirm & Ticket')
                    ->icon('heroicon-o-check-badge')
                    ->visible(fn (Appointment $record) => $record->status === 'offered' && ModuleGate::enabled('dispatch_board'))
                    ->action(function (Appointment $record) {
                        app(DispatchService::class)->confirmAppointment($record, auth()->id());
                        Notification::make()->title('Appointment confirmed & job ticket created')->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAppointments::route('/'),
        ];
    }
}
