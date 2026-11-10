<?php

namespace App\Filament\Resources\RescheduleRequests;

use App\Filament\Resources\RescheduleRequests\Pages\ManageRescheduleRequests;
use App\Models\AuditLog;
use App\Models\RescheduleRequest;
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
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RescheduleRequestResource extends Resource
{
    protected static ?string $model = RescheduleRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Booking & Dispatch';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return ModuleGate::enabled('reschedule_coordinator');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('appointment_id')->relationship('appointment', 'slot_code')->required(),
            DateTimePicker::make('original_starts_at')->required(),
            DateTimePicker::make('proposed_starts_at')->required(),
            DateTimePicker::make('proposed_ends_at'),
            TextInput::make('reason'),
            Select::make('status')->options([
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
            ])->required(),
            Textarea::make('ai_suggestion')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('appointment.slot_code')->label('Slot'),
                TextColumn::make('original_starts_at')->dateTime(),
                TextColumn::make('proposed_starts_at')->dateTime(),
                TextColumn::make('status')->badge(),
                TextColumn::make('ai_suggestion')->limit(40)->toggleable(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->visible(fn (RescheduleRequest $record) => $record->status === 'pending' && ModuleGate::enabled('human_approval'))
                    ->action(function (RescheduleRequest $record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);
                        $record->appointment?->update([
                            'starts_at' => $record->proposed_starts_at,
                            'ends_at' => $record->proposed_ends_at ?? $record->proposed_starts_at->copy()->addHours(2),
                            'status' => 'confirmed',
                        ]);
                        AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'reschedule.approved',
                            'subject_type' => RescheduleRequest::class,
                            'subject_id' => $record->id,
                            'summary' => 'Approved reschedule for '.$record->appointment?->slot_code,
                        ]);
                        Notification::make()->title('Reschedule approved')->success()->send();
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
            'index' => ManageRescheduleRequests::route('/'),
        ];
    }
}
