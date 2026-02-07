@props(['employee'])

@php
    $locale = app()->getLocale();
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Arabic Name --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Arabic Name') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->name_ar ?: '—' }}
            </div>
        </div>

        {{-- English Name --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('English Name') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->name_en ?: '—' }}
            </div>
        </div>

        {{-- National ID --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('National ID') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->national_id ?: '—' }}
            </div>
        </div>

        {{-- National ID Expiry --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('National ID Expiry') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ company_date($employee->national_id_expiry) ?: '—' }}
            </div>
        </div>

        {{-- Nationality --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Nationality') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->nationality ?: '—' }}
            </div>
        </div>

        {{-- Gender --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Gender') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                @if($employee->gender === 'male')
                    {{ tr('Male') }}
                @elseif($employee->gender === 'female')
                    {{ tr('Female') }}
                @else
                    —
                @endif
            </div>
        </div>

        {{-- Social Status --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Social Status') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                @if($employee->marital_status === 'single')
                    {{ tr('Single') }}
                @elseif($employee->marital_status === 'married')
                    {{ tr('Married') }}
                @else
                    —
                @endif
            </div>
        </div>

        {{-- Birth Date --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Birth Date') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ company_date($employee->birth_date) ?: '—' }}
            </div>
        </div>

        {{-- Birth Place --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Birth Place') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->birth_place ?: '—' }}
            </div>
        </div>

        {{-- Children Count --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Children Count') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->children_count ?? '—' }}
            </div>
        </div>
    </div>
</div>




