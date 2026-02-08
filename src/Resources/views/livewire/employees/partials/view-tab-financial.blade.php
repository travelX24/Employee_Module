@props(['employee'])

@php
    // حساب الأجور المشتقة
    $wages = $employee->calculateWages();
    // حساب رصيد الإجازات
    $leaveBalance = $employee->calculateLeaveBalance();
@endphp

<div class="space-y-6">
    {{-- قسم المعلومات الأساسية --}}
    <div>
        <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
            <i class="fas fa-file-contract text-[color:var(--brand-via)]"></i>
            {{ tr('Contract & Salary Information') }}
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- Contract Type --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    {{ tr('Contract Type') }}
                </label>
                <div class="px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 text-sm">
                    @php
                        $contractText = match ($employee->contract_type) {
                            'permanent' => tr('Permanent'),
                            'temporary' => tr('Temporary'),
                            'probation' => tr('Probation'),
                            'contractor' => tr('Contractor'),
                            default => $employee->contract_type ?: '—',
                        };
                    @endphp
                    {{ $contractText }}
                </div>
            </div>

            {{-- Basic Salary --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    {{ tr('Basic Salary') }}
                </label>
                <div class="px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 text-sm font-bold">
                    {{ $employee->basic_salary ? number_format($employee->basic_salary, 2) : '—' }}
                </div>
            </div>

            {{-- Allowance --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    {{ tr('Allowance') }}
                </label>
                <div class="px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 text-sm">
                    {{ $employee->allowances ? number_format($employee->allowances, 2) : '—' }}
                </div>
            </div>

            {{-- Contract Duration --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    {{ tr('Contract Duration (Months)') }}
                </label>
                <div class="px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 text-sm">
                    {{ $employee->contract_duration_months ?: '—' }}
                </div>
            </div>

            {{-- Annual Leave Days --}}
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    {{ tr('Annual Leave Days') }}
                </label>
                <div class="px-4 py-2.5 bg-gray-50 rounded-lg border border-gray-200 text-gray-900 text-sm">
                    {{ $employee->annual_leave_days ?: '—' }}
                </div>
            </div>
        </div>
    </div>

    {{-- قسم الأجور المشتقة --}}
    @if($wages)
        <div>
            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-calculator text-[color:var(--brand-via)]"></i>
                {{ tr('Calculated Wages') }}
                <span class="text-xs text-gray-500 font-normal">({{ tr('Based on work schedule') }})</span>
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                {{-- الأجر اليومي --}}
                <div class="text-center">
                    <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Daily Wage') }}</div>
                    <div class="p-3 bg-white rounded-lg shadow-sm border border-gray-200">
                        <span class="text-2xl font-extrabold text-gray-800">{{ number_format($wages['daily_wage'], 0) }}</span>
                        <p class="text-xs text-gray-500 mt-1">{{ tr('per day') }}</p>
                    </div>
                </div>

                {{-- الأجر بالساعة --}}
                <div class="text-center">
                    <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Hourly Wage') }}</div>
                    <div class="p-3 bg-white rounded-lg shadow-sm border border-gray-200">
                        <span class="text-2xl font-extrabold text-gray-800">{{ number_format($wages['hourly_wage'], 2) }}</span>
                        <p class="text-xs text-gray-500 mt-1">{{ tr('per hour') }}</p>
                    </div>
                </div>

                {{-- الأجر بالدقيقة --}}
                <div class="text-center">
                    <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Minute Wage') }}</div>
                    <div class="p-3 bg-white rounded-lg shadow-sm border border-gray-200">
                        <span class="text-2xl font-extrabold text-gray-800">{{ number_format($wages['minute_wage'], 2) }}</span>
                        <p class="text-xs text-gray-500 mt-1">{{ tr('per minute') }}</p>
                    </div>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2 text-center">
                <i class="fas fa-info-circle"></i>
                {{ tr('These wages are automatically calculated based on the basic salary and work schedule') }}
            </p>
        </div>
    @else
        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800 flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                {{ tr('Calculated wages are not available. Please assign a work schedule to this employee.') }}
            </p>
        </div>
    @endif

    {{-- قسم رصيد الإجازات --}}
    <div>
        <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
            <i class="fas fa-calendar-check text-[color:var(--brand-via)]"></i>
            {{ tr('Leave Balance Details') }}
        </h4>
        <div class="p-5 bg-gray-50 rounded-xl border-2 border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- نوع الموظف --}}
                <div>
                    <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Employee Type') }}</div>
                    <div class="p-3 bg-white rounded-lg border border-gray-200">
                        @if($employee->is_transferred_employee)
                            <span class="inline-flex items-center gap-2 text-sm font-bold text-gray-800">
                                <i class="fas fa-exchange-alt text-[color:var(--brand-via)]"></i>
                                {{ tr('Transferred Employee') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-2 text-sm font-bold text-gray-800">
                                <i class="fas fa-user-plus text-[color:var(--brand-via)]"></i>
                                {{ tr('New Employee') }}
                            </span>
                        @endif
                    </div>
                </div>

                @if($employee->is_transferred_employee)
                    {{-- الرصيد الافتتاحي --}}
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Opening Balance') }}</div>
                        <div class="p-3 bg-white rounded-lg border border-gray-200 text-center">
                            <span class="text-xl font-bold {{ $employee->opening_leave_balance < 0 ? 'text-red-600' : 'text-gray-800' }}">
                                {{ $employee->opening_leave_balance ?? 0 }}
                            </span>
                            <p class="text-xs text-gray-500">{{ tr('days') }}</p>
                        </div>
                    </div>

                    {{-- التعديلات --}}
                    <div>
                        <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Adjustments') }}</div>
                        <div class="p-3 bg-white rounded-lg border border-gray-200 text-center">
                            <span class="text-xl font-bold {{ $employee->leave_balance_adjustments < 0 ? 'text-red-600' : ($employee->leave_balance_adjustments > 0 ? 'text-[color:var(--brand-via)]' : 'text-gray-800') }}">
                                {{ $employee->leave_balance_adjustments > 0 ? '+' : '' }}{{ $employee->leave_balance_adjustments ?? 0 }}
                            </span>
                            <p class="text-xs text-gray-500">{{ tr('days') }}</p>
                        </div>
                    </div>
                @else
                    {{-- عدد أيام العمل --}}
                    <div class="col-span-2">
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ tr('Balance calculated automatically based on hire date') }}:
                                <strong>{{ $employee->hired_at ? $employee->hired_at->format('Y-m-d') : '—' }}</strong>
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- الرصيد النهائي --}}
            <div class="mt-4 pt-4 border-t-2 border-gray-300">
                <div class="text-center">
                    <div class="text-xs font-semibold text-gray-600 mb-2">{{ tr('Current Leave Balance') }}</div>
                    <div class="p-4 bg-white rounded-xl shadow-sm border-2 border-gray-200">
                        <span class="text-4xl font-extrabold {{ $leaveBalance < 0 ? 'text-red-600' : 'text-[color:var(--brand-via)]' }}">
                            {{ $leaveBalance }}
                        </span>
                        <p class="text-sm text-gray-600 font-medium mt-2">{{ tr('Available Days') }}</p>
                        @if($leaveBalance < 0)
                            <p class="text-xs text-red-500 mt-1">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ tr('Negative balance') }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
