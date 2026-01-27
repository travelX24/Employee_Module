@props(['employee'])

<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Mobile --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Mobile') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->mobile ?: '—' }}
            </div>
        </div>

        {{-- Alternative Mobile --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Alternative Mobile') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->mobile_alt ?: '—' }}
            </div>
        </div>

        {{-- Work Email --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Work Email') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->email_work ?: '—' }}
            </div>
        </div>

        {{-- Personal Email --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Personal Email') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->email_personal ?: '—' }}
            </div>
        </div>

        {{-- City --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('City') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->city ?: '—' }}
            </div>
        </div>

        {{-- District --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('District') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->district ?: '—' }}
            </div>
        </div>

        {{-- Address --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Address') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->address ?: '—' }}
            </div>
        </div>
    </div>

    <div class="h-px bg-gray-100"></div>

    <div class="text-sm font-bold text-gray-900 mb-4">{{ tr('Emergency Contact') }}</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Emergency Phone --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Emergency Phone') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->emergency_contact_phone ?: '—' }}
            </div>
        </div>

        {{-- Emergency Name --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Emergency Name') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->emergency_contact_name ?: '—' }}
            </div>
        </div>

        {{-- Relation --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Relation') }}
            </label>
            <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-900">
                {{ $employee->emergency_contact_relation ?: '—' }}
            </div>
        </div>
    </div>
</div>




