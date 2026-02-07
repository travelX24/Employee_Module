@props(['employee'])

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Employee No --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Employee No') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->employee_no ?: '—' }}
            </div>
        </div>

        {{-- Department --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Main Department') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->department?->name ?: '—' }}
            </div>
        </div>

        {{-- Sub Department --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Sub Department') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->subDepartment?->name ?: '—' }}
            </div>
        </div>

        {{-- Job Title --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Job Title') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->jobTitle?->name ?: '—' }}
            </div>
        </div>

        {{-- Grade --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Grade') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->grade ?: '—' }}
            </div>
        </div>

        {{-- Manager --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Manager') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->manager?->name_ar ?: '—' }}
            </div>
        </div>

        {{-- Hire Date --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Hire Date') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ company_date($employee->hired_at) ?: '—' }}
            </div>
        </div>

        {{-- Status --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Status') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                @php
                    $statusText = match ($employee->status) {
                        'ACTIVE' => tr('Active'),
                        'ENDED' => tr('Ended'),
                        'ARCHIVED' => tr('Archived'),
                        default => $employee->status ?: '—',
                    };
                @endphp
                {{ $statusText }}
            </div>
        </div>
    </div>
</div>
