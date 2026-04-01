<div class="space-y-5">
    {{-- قسم العقد والراتب الأساسي --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Basic Salary --}}
        <x-ui.input 
            id="basic_salary_input"
            type="number"
            step="0.01"
            :label="tr('Basic Salary')" 
            wire:model.live.debounce.500ms="basic_salary" 
            error="basic_salary" 
            :required="true"
            placeholder="0.00"
        />
    </div>
    {{-- قسم الأجور المشتقة (ظهور فقط إذا كان هناك راتب أساسي) --}}
    @if(isset($daily_wage) && $daily_wage !== null)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <div class="col-span-full mb-2">
                <h4 class="text-sm font-bold text-[color:var(--brand-via)] flex items-center gap-2">
                    <i class="fas fa-calculator"></i>
                    {{ tr('Calculated Wages') }} <span class="text-xs text-gray-500 font-normal">({{ tr('Auto-calculated based on work schedule') }})</span>
                </h4>
            </div>

            {{-- الأجر اليومي --}}
            <x-ui.input 
                type="text"
                :label="tr('Daily Wage')" 
                :value="number_format((float)$daily_wage, 0)"
                readonly
                class="bg-white text-gray-800 font-semibold border-gray-300"
            />

            {{-- الأجر بالساعة --}}
            <x-ui.input 
                type="text"
                :label="tr('Hourly Wage')" 
                :value="number_format((float)$hourly_wage, 2)"
                readonly
                class="bg-white text-gray-800 font-semibold border-gray-300"
            />

            {{-- الأجر بالدقيقة --}}
            <x-ui.input 
                type="text"
                :label="tr('Minute Wage')" 
                :value="number_format((float)$minute_wage, 2)"
                readonly
                class="bg-white text-gray-800 font-semibold border-gray-300"
            />
        </div>
    @endif

    {{-- قسم البدلات والإجازة البسيطة --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Allowance --}}
        <x-ui.input 
            id="allowance_input"
            type="number"
            step="0.01"
            :label="tr('Allowance')" 
            wire:model="allowance" 
            value="{{ $allowance }}"
            error="allowance"
            placeholder="0.00"
        />

        {{-- Annual Leave Field Removed as per request --}}
    </div>

    {{-- قسم الإجازة السنوية المتقدم --}}
    @if(true)
        <div class="p-5 border-2 border-[color:var(--brand-via)]/20 rounded-xl bg-gradient-to-br from-[color:var(--brand-via)]/5 to-transparent">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-calendar-alt text-[color:var(--brand-via)]"></i>
                    {{ tr('Annual Leave Balance Management') }}
                </h3>
            </div>

            {{-- Checkbox موظف منقول --}}
            <div class="mb-4">
                <label class="inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        wire:model.live="is_transferred_employee" 
                        class="w-5 h-5 text-[color:var(--brand-via)] rounded border-gray-300 focus:ring-[color:var(--brand-via)]"
                    />
                    <span class="mr-3 text-sm font-semibold text-gray-700">{{ tr('Transferred Employee') }}</span>
                    <span class="text-xs text-gray-500">({{ tr('Employee transferred from another system') }})</span>
                </label>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @if($is_transferred_employee)
                    {{-- الرصيد الافتتاحي --}}
                    <div>
                        <x-ui.input 
                            id="opening_balance_input"
                            type="number"
                            :label="tr('Opening Balance')" 
                            wire:model.live.debounce.500ms="opening_leave_balance" 
                            error="opening_leave_balance"
                            placeholder="0"
                        />
                        <p class="text-xs text-gray-500 mt-1">{{ tr('Can be negative') }}</p>
                    </div>
                @else
                    <div>
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg h-full flex items-center">
                            <p class="text-sm text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ tr('Leave balance will be calculated automatically based on hire date') }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- الرصيد النهائي مع أزرار التحكم --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ tr('Current Balance / Adjustments') }}</label>
                    <div class="p-4 bg-white rounded-lg border-2 border-dashed border-[color:var(--brand-via)]/30 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button type="button" wire:click="subtractLeaveDay" class="w-8 h-8 rounded-full bg-red-100 text-red-600 flex items-center justify-center hover:bg-red-200 transition-colors">
                                <i class="fas fa-minus"></i>
                            </button>
                            <div class="text-center px-4">
                                <span class="text-3xl font-extrabold text-[color:var(--brand-via)]">{{ $calculated_leave_balance }}</span>
                                <p class="text-[10px] text-gray-500 uppercase tracking-wider font-bold">{{ tr('Available Days') }}</p>
                            </div>
                            <button type="button" wire:click="addLeaveDay" class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200 transition-colors">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <div class="text-xs text-gray-400 border-r pr-3">
                            {{ tr('Adjustments') }}: <span class="font-bold {{ $leave_balance_adjustments >= 0 ? 'text-green-500' : 'text-red-500' }}">{{ $leave_balance_adjustments > 0 ? '+' : '' }}{{ $leave_balance_adjustments }}</span>
                        </div>
                    </div>
                    </div>
                </div>
            </div>

            {{-- Adjustment History List --}}
            @if(isset($employee) && $employee->leaveAdjustments && $employee->leaveAdjustments->count() > 0)
                <div class="mt-6 pt-6 border-t border-gray-100">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                        <i class="fas fa-history"></i>
                        {{ tr('Manual Adjustments History') }}
                    </h4>
                    <div class="space-y-2 max-h-[250px] overflow-y-auto no-scrollbar pr-1">
                        @foreach($employee->leaveAdjustments as $adj)
                            <div class="flex items-start gap-3 p-3 bg-white border border-gray-100 rounded-xl hover:border-gray-200 transition-colors group">
                                <div @class([
                                    'w-8 h-8 rounded-lg flex items-center justify-center shrink-0 mt-0.5 shadow-sm',
                                    'bg-green-50 text-green-600' => $adj->amount > 0,
                                    'bg-red-50 text-red-600' => $adj->amount < 0,
                                ])>
                                    <i class="fas {{ $adj->amount > 0 ? 'fa-plus' : 'fa-minus' }} text-[10px]"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[11px] font-bold text-gray-800">{{ $adj->amount > 0 ? '+' : '' }}{{ number_format($adj->amount, 0) }} {{ tr('Days') }}</span>
                                        <span class="text-[10px] text-gray-400 font-medium">{{ $adj->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                    <p class="text-[11px] text-gray-600 mt-1 leading-relaxed">{{ $adj->reason }}</p>
                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center gap-1.5">
                                            <div class="w-5 h-5 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center group-hover:bg-indigo-50 group-hover:border-indigo-100 transition-colors">
                                                <i class="fas fa-user-shield text-[9px] text-gray-400 group-hover:text-indigo-500"></i>
                                            </div>
                                            <span class="text-[10px] text-gray-500 font-medium">{{ $adj->performer?->name ?? tr('System') }}</span>
                                        </div>
                                        @if($adj->file_path)
                                            <a href="{{ asset('storage/'.$adj->file_path) }}" target="_blank" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1 bg-indigo-50 group-hover:bg-indigo-100 px-2.5 py-1 rounded-lg transition-colors">
                                                <i class="fas fa-file-download text-[11px]"></i>
                                                {{ tr('View File') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Leave Adjustment Modal --}}
    @if(isset($employee))
        <x-ui.modal wire:model="showingAdjustmentModal" maxWidth="md">
            <x-slot name="title">
                <div class="flex items-center gap-2">
                    @if($adjustmentType === 'add')
                        <div class="w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                            <i class="fas fa-plus-circle"></i>
                        </div>
                        <span class="text-gray-800">{{ tr('Increase Leave Balance') }}</span>
                    @else
                        <div class="w-8 h-8 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                            <i class="fas fa-minus-circle"></i>
                        </div>
                        <span class="text-gray-800">{{ tr('Decrease Leave Balance') }}</span>
                    @endif
                </div>
            </x-slot>
    
            <div class="space-y-4 p-5">
                <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 flex items-start gap-3">
                    <i class="fas fa-info-circle text-indigo-500 mt-1"></i>
                    <div class="text-xs text-indigo-800 leading-relaxed">
                        {{ tr('You are about to') }} 
                        <span class="font-bold {{ $adjustmentType === 'add' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $adjustmentType === 'add' ? tr('ADD 1 day') : tr('SUBTRACT 1 day') }}
                        </span>
                        {{ tr('from the employee balance. Correcting or adjusting balances requires documentation for audit purposes.') }}
                    </div>
                </div>
    
                <x-ui.textarea 
                    id="adjustment_reason"
                    :label="tr('Adjustment Reason')" 
                    wire:model="adjustmentReason" 
                    error="adjustmentReason"
                    :required="true"
                    rows="3"
                    placeholder="{{ tr('Write a detailed reason for this adjustment (e.g. Compensation for weekend work, administrative decision #123)...') }}"
                />
    
                <x-ui.input 
                    id="adjustment_file"
                    type="file"
                    :label="tr('Attachment (PDF/Image)')" 
                    wire:model="adjustmentFile" 
                    error="adjustmentFile"
                    :required="true"
                    class="file:bg-indigo-50 file:text-indigo-600 file:border-0 file:rounded-lg file:px-3 file:py-1.5 file:mr-4 file:font-bold hover:file:bg-indigo-100 cursor-pointer"
                />
                
                <div wire:loading wire:target="adjustmentFile" class="flex items-center gap-2 text-xs text-indigo-600 font-bold bg-indigo-50 px-3 py-2 rounded-lg">
                    <i class="fas fa-spinner fa-spin"></i> {{ tr('Uploading attachment...') }}
                </div>
    
                @if($adjustmentFile && $adjustmentFile instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                    <div class="flex items-center gap-2 text-[10px] text-green-600 font-bold bg-green-50 px-3 py-1.5 rounded-lg border border-green-100">
                        <i class="fas fa-check-circle"></i>
                        {{ $adjustmentFile->getClientOriginalName() }}
                    </div>
                @endif
            </div>
    
            <x-slot name="footer">
                <div class="flex justify-end gap-3 w-full">
                    <x-ui.secondary-button type="button" @click="$wire.set('showingAdjustmentModal', false)">
                        {{ tr('Cancel') }}
                    </x-ui.secondary-button>
                    <x-ui.primary-button type="button" wire:click="confirmLeaveAdjustment" wire:loading.attr="disabled">
                        <i class="fas fa-save mr-2"></i>
                        {{ tr('Confirm Adjustment') }}
                    </x-ui.primary-button>
                </div>
            </x-slot>
        </x-ui.modal>
    @endif
</div>
