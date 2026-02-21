@props(['employee'])

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Employee No --}}
         {{-- Employee No --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Employee No') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->employee_no ?: '—' }}
            </div>
        </div>

        {{-- Branch --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Branch') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                @php
                    $branchText = '—';

                    // 1) لو relation branch محمّلة استخدمها (بدون lazy load)
                    if (method_exists($employee, 'relationLoaded') && $employee->relationLoaded('branch') && $employee->branch) {
                        $branchText = $employee->branch->name_ar ?? $employee->branch->name ?? $employee->branch->name_en ?? '—';
                    } else {
                        // 2) fallback: جيب الاسم بالاستعلام (safe حتى لو lazy loading disabled)
                        try {
                            if (!empty($employee->branch_id)) {
                                $b = \App\Models\Branch::query()->find((int) $employee->branch_id);
                                if ($b) {
                                    $isAr = substr((string) app()->getLocale(), 0, 2) === 'ar';
                                    $branchText = $isAr
                                        ? ($b->name_ar ?? $b->name ?? $b->name_en ?? '—')
                                        : ($b->name_en ?? $b->name ?? $b->name_ar ?? '—');
                                }
                            }
                        } catch (\Throwable $e) {}
                    }
                @endphp

                {{ $branchText }}
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
