<?php

namespace App\Filament\Resources\ServiceRequests;

use App\Filament\Resources\ServiceRequests\Pages\ManageServiceRequests;
use App\Models\ServiceRequest;
use App\Services\AiSchedulingService;
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
use Illuminate\Support\Str;
use UnitEnum;

class ServiceRequestResource extends Resource
{
    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Booking & Dispatch';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return ModuleGate::enabled('booking_intake');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('ticket_no')->default(fn () => 'SR-'.Str::upper(Str::random(6)))->required(),
            Select::make('customer_id')->relationship('customer', 'name')->searchable()->required(),
            TextInput::make('service_type')->required(),
            Select::make('priority')->options([
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'High',
                'emergency' => 'Emergency',
            ])->required(),
            Select::make('status')->options([
                'intake' => 'Intake',
                'slot_offered' => 'Slot Offered',
                'dispatched' => 'Dispatched',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled',
            ])->required(),
            Select::make('source')->options([
                'phone' => 'Phone',
                'web' => 'Web',
                'jobber' => 'Jobber',
                'servicetitan' => 'ServiceTitan',
            ]),
            TextInput::make('address'),
            TextInput::make('zip'),
            DateTimePicker::make('preferred_start'),
            DateTimePicker::make('preferred_end'),
            Select::make('assigned_technician_id')->relationship('technician', 'name')->searchable(),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_no')->searchable()->sortable(),
                TextColumn::make('customer.name')->searchable(),
                TextColumn::make('service_type')->limit(30),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('zip'),
                TextColumn::make('preferred_start')->dateTime()->toggleable(),
            ])
            ->recordActions([
                Action::make('aiDraft')
                    ->label('AI Slot Draft')
                    ->icon('heroicon-o-sparkles')
                    ->visible(fn () => ModuleGate::enabled('ai_scheduling_assist'))
                    ->action(function (ServiceRequest $record) {
                        app(AiSchedulingService::class)->draftSlotOffer($record, auth()->id());
                        Notification::make()->title('AI slot offer drafted')->success()->send();
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
            'index' => ManageServiceRequests::route('/'),
        ];
    }
}
