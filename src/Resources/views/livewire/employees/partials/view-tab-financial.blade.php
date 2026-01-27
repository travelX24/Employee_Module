@props(['employee'])

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Contract Type --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Contract Type') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
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
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Basic Salary') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->basic_salary ? number_format($employee->basic_salary, 2) : '—' }}
            </div>
        </div>

        {{-- Allowance --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Allowance') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->allowances ? number_format($employee->allowances, 2) : '—' }}
            </div>
        </div>

        {{-- Contract Duration --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Contract Duration (Months)') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->contract_duration_months ?: '—' }}
            </div>
        </div>

        {{-- Annual Leave Days --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Annual Leave Days') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->annual_leave_days ?: '—' }}
            </div>
        </div>
    </div>
</div>




