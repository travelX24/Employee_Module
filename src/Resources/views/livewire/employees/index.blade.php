@php
    $locale = app()->getLocale();
    $isRtl  = in_array(substr($locale, 0, 2), ['ar','fa','ur','he']);
    $dir    = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('topbar-left-content')
    <x-ui.page-header
        :title="tr('Employees')"
        :subtitle="tr('Search and filter employees')"
        class="!flex-col {{ $isRtl ? '!items-end !text-right' : '!items-start !text-left' }} !justify-start !gap-1"
        titleSize="xl"
    />
@endsection

<div class="space-y-4 sm:space-y-6" dir="{{ $dir }}">

    <div class="space-y-4 sm:space-y-6">
        {{-- Search and Filters (مثل Companies) --}}
        <x-ui.card>
            <div class="space-y-4">
                {{-- Search --}}
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <x-ui.search-box
                            model="search"
                            :placeholder="tr('Search by name / employee no / email / mobile ...')"
                            :debounce="300"
                        />
                    </div>
                
                    <div class="flex flex-wrap items-center gap-2 sm:shrink-0">
                        @can('employees.export')
                        <x-ui.secondary-button
                            wire:click="openExportModal"
                            :fullWidth="false"
                            class="!border-amber-200 !bg-amber-50/50 !text-amber-700 hover:!bg-amber-100"
                        >
                            <i class="fas fa-file-export"></i>
                            <span class="ms-2">{{ tr('Export') }}</span>
                        </x-ui.secondary-button>
                        @endcan

                        @can('employees.create') {{-- Using create for import as it creates many --}}
                        <x-ui.secondary-button
                            wire:click="openImportModal"
                            :fullWidth="false"
                        >
                            <i class="fas fa-file-import"></i>
                            <span class="ms-2">{{ tr('Import Employees') }}</span>
                        </x-ui.secondary-button>
                        @endcan

                        @can('employees.create')
                        <x-ui.primary-button
                            href="{{ route('company-admin.employees.create') }}"
                            :arrow="false"
                            :fullWidth="false"
                        >
                            <i class="fas fa-plus"></i>
                            <span class="ms-2">{{ tr('Add Employee') }}</span>
                        </x-ui.primary-button>
                        @endcan
                    </div>

                </div>
                

                {{-- Filters + View Toggle --}}
                <div x-data="{ open: @js(true) }" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <button
                            type="button"
                            @click="open = !open"
                            class="flex items-center justify-between text-sm font-semibold text-gray-700 hover:text-gray-900 transition-colors"
                        >
                            <span class="flex items-center gap-2">
                                <i class="fas fa-filter"></i>
                                <span>{{ tr('Filters') }}</span>
                            </span>
                            <i class="fas fa-chevron-down transition-transform ms-2" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        {{-- View Toggle Buttons (نفس Companies) --}}
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="setViewMode('list')"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200
                                    {{ $viewMode === 'list'
                                        ? 'bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-lg hover:shadow-xl hover:from-amber-600 hover:to-yellow-600'
                                        : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 shadow-sm' }}"
                                title="{{ tr('List View') }}"
                            >
                                <i class="fas fa-list"></i>
                            </button>

                            <button
                                type="button"
                                wire:click="setViewMode('cards')"
                                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200
                                    {{ $viewMode === 'cards'
                                        ? 'bg-gradient-to-r from-amber-500 to-yellow-500 text-white shadow-lg hover:shadow-xl hover:from-amber-600 hover:to-yellow-600'
                                        : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 shadow-sm' }}"
                                title="{{ tr('Card View') }}"
                            >
                                <i class="fas fa-th"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Filters Content --}}
                    <div
                        x-show="open"
                        x-transition
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 items-end"
                    >
                        <x-ui.filter-select
                            model="departmentId"
                            :label="tr('Department')"
                            :placeholder="tr('All Departments')"
                            :options="$departmentsOptions"
                            width="full"
                            :defer="false"
                            :applyOnChange="true"
                            allValue="all"
                        />

                        <x-ui.filter-select
                            model="jobTitleId"
                            :label="tr('Job Title')"
                            :placeholder="tr('All Job Titles')"
                            :options="$jobTitlesOptions"
                            width="full"
                            :defer="false"
                            :applyOnChange="true"
                            allValue="all"
                        />

                        <x-ui.filter-select
                            model="status"
                            :label="tr('Status')"
                            :placeholder="tr('All Status')"
                            :options="[
                                ['value' => 'ACTIVE', 'label' => tr('Active')],
                                ['value' => 'SUSPENDED', 'label' => tr('Suspended')],
                                ['value' => 'RESIGNED', 'label' => tr('Resigned')],
                                ['value' => 'TERMINATED', 'label' => tr('Terminated')],
                                ['value' => 'RETIRED', 'label' => tr('Retired')],
                            ]"
                            width="full"
                            :defer="false"
                            :applyOnChange="true"
                            allValue="all"
                        />

                        {{-- Hiring Date Filter --}}
                        <div class="flex flex-col w-full">
                            <x-ui.filter-select
                                model="hiringDateType"
                                :label="tr('Hiring Date')"
                                :placeholder="tr('All Dates')"
                                :options="[
                                    ['value' => 'this_month', 'label' => tr('This Month')],
                                    ['value' => 'last_3_months', 'label' => tr('Last 3 Months')],
                                    ['value' => 'this_year', 'label' => tr('This Year')],
                                    ['value' => 'custom', 'label' => tr('Custom Range')],
                                ]"
                                width="full"
                                :defer="false"
                                :applyOnChange="true"
                                allValue="all"
                            />
                        
                            @if($hiringDateType === 'custom')
                                <div class="flex items-center gap-2 mt-2" x-transition>
                                    <x-ui.company-date-picker
                                        model="hiringDateStart"
                                        class="w-full"
                                    />
                                    <span class="text-gray-400">-</span>
                                    <x-ui.company-date-picker
                                        model="hiringDateEnd"
                                        class="w-full"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Clear Filters Button (مثل Companies) --}}
                    <div
                        x-data="{
                            hasFilters() {
                                const hasSearch = ($wire?.search ?? '').trim() !== '';
                                const filterSelects = document.querySelectorAll('select.hidden[wire\\:model], select.hidden[wire\\:model\\.defer], select.hidden[wire\\:model\\.live]');
                                const hasSelect = Array.from(filterSelects).some(el => {
                                    const value = el.value;
                                    return value && value !== '' && value !== 'all';
                                });
                                return hasSearch || hasSelect;
                            },
                            clearAll() {
                                if ($wire && typeof $wire.clearAllFilters === 'function') {
                                    $wire.clearAllFilters();
                                }
                            }
                        }"
                        x-show="hasFilters()"
                        x-transition
                        class="flex items-center justify-end"
                    >
                        <button
                            type="button"
                            @click="clearAll()"
                            wire:loading.attr="disabled"
                            wire:target="clearAllFilters"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-gray-900 transition-colors disabled:opacity-50"
                        >
                            <i class="fas fa-times" wire:loading.remove wire:target="clearAllFilters"></i>
                            <i class="fas fa-spinner fa-spin" wire:loading wire:target="clearAllFilters"></i>
                            <span wire:loading.remove wire:target="clearAllFilters">{{ tr('Clear all filters') }}</span>
                            <span wire:loading wire:target="clearAllFilters">{{ tr('Clearing...') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Employees Display --}}
        @if($employees->count() > 0)

            @if($viewMode === 'list')
                {{-- List View --}}
                <x-ui.card>
                    <x-ui.table
                       :headers="[
                                tr('Employee No'),
                                tr('Name'),
                                tr('Department'),
                                tr('Branch'),
                                tr('Job Title'),
                                tr('Mobile'),
                                tr('Email'),
                                tr('Status'),
                                tr('Actions'),
                            ]"

                        :rtl="$isRtl"
                        :perPage="10"
                    >
                        @foreach($employees as $emp)
                            @php
                                $statusType = match ($emp->status) {
                                    'ACTIVE'   => 'success',
                                    'ENDED'    => 'warning',
                                    'ARCHIVED' => 'default',
                                    default    => 'default',
                                };

                                $statusText = match ($emp->status) {
                                    'ACTIVE'   => tr('Active'),
                                    'ENDED'    => tr('Ended'),
                                    'ARCHIVED' => tr('Archived'),
                                    default    => $emp->status ?: '—',
                                };

                                $primaryName = $isRtl
                                    ? ($emp->name_ar ?: $emp->name_en)
                                    : ($emp->name_en ?: $emp->name_ar);

                                $secondaryName = $isRtl
                                    ? ($emp->name_en ?: null)
                                    : ($emp->name_ar ?: null);
                            @endphp

                            <tr wire:key="emp-row-{{ $emp->id }}" class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                                <td class="py-3 px-3 font-semibold text-gray-900 whitespace-nowrap">
                                    {{ $emp->employee_no }}
                                </td>

                                <td class="py-3 px-3">
                                    <div class="font-semibold text-gray-900 text-sm truncate" title="{{ $primaryName }}">
                                        {{ $primaryName ?: '—' }}
                                    </div>
                                    @if($secondaryName && $secondaryName !== $primaryName)
                                        <div class="text-xs text-gray-500 truncate mt-0.5" title="{{ $secondaryName }}">
                                            {{ $secondaryName }}
                                        </div>
                                    @endif
                                </td>

                             <td class="py-3 px-3">
                                    <span class="text-sm text-gray-700">
                                        {{ $emp->department?->name ?? '—' }}
                                    </span>
                                </td>

                                @php
                                    $branchesMapLocal = $branchesMap ?? [];
                                    $branchRow = $emp->branch_id ? ($branchesMapLocal[(int) $emp->branch_id] ?? null) : null;

                                    $branchName = $isRtl
                                        ? ($branchRow['name_ar'] ?? $branchRow['name'] ?? $branchRow['name_en'] ?? null)
                                        : ($branchRow['name_en'] ?? $branchRow['name'] ?? $branchRow['name_ar'] ?? null);

                                    $branchCode = $branchRow['code'] ?? null;
                                @endphp

                                <td class="py-3 px-3">
                                    <div class="text-sm text-gray-700 truncate" title="{{ $branchName ?: '' }}">
                                        {{ $branchName ?: ($emp->branch_id ? ('#' . $emp->branch_id) : '—') }}
                                    </div>
                                    @if($branchCode)
                                        <div class="text-xs text-gray-500 mt-0.5 truncate">{{ $branchCode }}</div>
                                    @endif
                                </td>

                                <td class="py-3 px-3">
                                    <span class="text-sm text-gray-700">
                                        {{ $emp->jobTitle?->name ?? '—' }}
                                    </span>
                                </td>


                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="text-sm text-gray-700">
                                        {{ $emp->mobile ?? '—' }}
                                    </span>
                                </td>

                                <td class="py-3 px-3">
                                    <span class="text-sm text-gray-700">
                                        {{ $emp->email_work ?? '—' }}
                                    </span>
                                </td>

                                <td class="py-3 px-3 whitespace-nowrap">
                                    <x-ui.badge :type="$statusType" size="sm">
                                        {{ $statusText }}
                                    </x-ui.badge>
                                </td>

                                <td class="py-3 px-3">
                                    <x-ui.dropdown-menu>
                                        @can('employees.edit')
                                        <x-ui.dropdown-item
                                            href="#"
                                            x-on:click="$dispatch('open-view-employee-{{ $emp->id }}')"
                                        >
                                            <i class="fas fa-eye w-4 me-2"></i>
                                            {{ tr('View & Edit') }}
                                        </x-ui.dropdown-item>
                                        @elsecan('employees.view')
                                        <x-ui.dropdown-item
                                            href="#"
                                            x-on:click="$dispatch('open-view-employee-{{ $emp->id }}')"
                                        >
                                            <i class="fas fa-eye w-4 me-2"></i>
                                            {{ tr('View Details') }}
                                        </x-ui.dropdown-item>
                                        @endcan
                                        
                                        @can('employees.edit')
                                        @if($emp->status === 'ACTIVE')
                                            <x-ui.dropdown-item
                                                href="#"
                                                wire:click="openDeactivateModal({{ $emp->id }})"
                                                class="text-red-600 hover:bg-red-50"
                                            >
                                                <i class="fas fa-ban w-4 me-2"></i>
                                                {{ tr('Deactivate') }}
                                            </x-ui.dropdown-item>
                                        @else
                                            <x-ui.dropdown-item
                                                href="#"
                                                wire:click="activateEmployee({{ $emp->id }})"
                                                class="text-green-600 hover:bg-green-50"
                                            >
                                                <i class="fas fa-check-circle w-4 me-2"></i>
                                                {{ tr('Activate') }}
                                            </x-ui.dropdown-item>
                                        @endif
                                        @endcan

                                        @can('employees.delete') {{-- Termination/Archive treated as soft delete/lifecycle --}}
                                        <x-ui.dropdown-item
                                            href="#"
                                            wire:click="openTerminationModal({{ $emp->id }})"
                                            class="text-gray-500 hover:bg-gray-50"
                                        >
                                            <i class="fas fa-archive w-4 me-2"></i>
                                            {{ tr('End of Service / Archive') }}
                                        </x-ui.dropdown-item>
                                        @endcan
                                    </x-ui.dropdown-menu>
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </x-ui.card>

            @else
                {{-- Cards View --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                    @foreach($employees as $emp)
                        @php
                            $statusType = match ($emp->status) {
                                'ACTIVE'   => 'success',
                                'ENDED'    => 'warning',
                                'ARCHIVED' => 'default',
                                default    => 'default',
                            };

                            $statusText = match ($emp->status) {
                                'ACTIVE'   => tr('Active'),
                                'ENDED'    => tr('Ended'),
                                'ARCHIVED' => tr('Archived'),
                                default    => $emp->status ?: '—',
                            };

                            $primaryName = $isRtl
                                ? ($emp->name_ar ?: $emp->name_en)
                                : ($emp->name_en ?: $emp->name_ar);
                        @endphp

                        <x-ui.card hover="true">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-xl flex-shrink-0 overflow-hidden {{ $emp->documents->firstWhere('type', 'personal_photo') ? 'border border-gray-200' : 'bg-gradient-to-br from-[color:var(--brand-from)] via-[color:var(--brand-via)] to-[color:var(--brand-to)] flex items-center justify-center' }}">
                                        @if($photo = $emp->documents->firstWhere('type', 'personal_photo'))
                                            <img src="{{ asset('storage/' . $photo->file_path) }}" alt="{{ $primaryName }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="fas fa-user text-white text-lg"></i>
                                        @endif
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-gray-900 text-sm sm:text-base truncate" title="{{ $primaryName }}">
                                            {{ $primaryName ?: '—' }}
                                        </h3>
                                        <p class="text-xs text-gray-500 truncate mt-1">
                                            <i class="fas fa-id-badge me-1"></i>
                                            {{ tr('Employee No') }}: {{ $emp->employee_no ?: '—' }}
                                        </p>
                                    </div>
                                </div>

                                <x-ui.dropdown-menu>
                                    @can('employees.edit')
                                    <x-ui.dropdown-item
                                        href="#"
                                        x-on:click="$dispatch('open-view-employee-{{ $emp->id }}')"
                                    >
                                        <i class="fas fa-eye w-4 me-2"></i>
                                        {{ tr('View & Edit') }}
                                    </x-ui.dropdown-item>
                                    @elsecan('employees.view')
                                    <x-ui.dropdown-item
                                        href="#"
                                        x-on:click="$dispatch('open-view-employee-{{ $emp->id }}')"
                                    >
                                        <i class="fas fa-eye w-4 me-2"></i>
                                        {{ tr('View Details') }}
                                    </x-ui.dropdown-item>
                                    @endcan

                                    @can('employees.edit')
                                    @if($emp->status === 'ACTIVE')
                                        <x-ui.dropdown-item
                                            href="#"
                                            wire:click="openDeactivateModal({{ $emp->id }})"
                                            class="text-red-600 hover:bg-red-50"
                                        >
                                            <i class="fas fa-ban w-4 me-2"></i>
                                            {{ tr('Deactivate') }}
                                        </x-ui.dropdown-item>
                                    @else
                                        <x-ui.dropdown-item
                                            href="#"
                                            wire:click="activateEmployee({{ $emp->id }})"
                                            class="text-green-600 hover:bg-green-50"
                                        >
                                            <i class="fas fa-check-circle w-4 me-2"></i>
                                            {{ tr('Activate') }}
                                        </x-ui.dropdown-item>
                                    @endif
                                    @endcan

                                    @can('employees.delete')
                                    <x-ui.dropdown-item
                                        href="#"
                                        wire:click="openTerminationModal({{ $emp->id }})"
                                        class="text-gray-500 hover:bg-gray-50"
                                    >
                                        <i class="fas fa-archive w-4 me-2"></i>
                                        {{ tr('End of Service / Archive') }}
                                    </x-ui.dropdown-item>
                                    @endcan
                                </x-ui.dropdown-menu>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ tr('Status') }}</span>
                                    <x-ui.badge :type="$statusType" size="sm">
                                        {{ $statusText }}
                                    </x-ui.badge>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ tr('Department') }}</span>
                                    <span class="text-xs font-medium text-gray-700 truncate ms-2">
                                        {{ $emp->department?->name ?? '—' }}
                                    </span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-gray-500">{{ tr('Job Title') }}</span>
                                    <span class="text-xs font-medium text-gray-700 truncate ms-2">
                                        {{ $emp->jobTitle?->name ?? '—' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <i class="fas fa-phone"></i>
                                    <span class="truncate">{{ $emp->mobile ?? '—' }}</span>
                                </div>

                                <div class="flex items-center gap-2 text-xs text-gray-500">
                                    <i class="fas fa-envelope"></i>
                                    <span class="truncate">{{ $emp->email_work ?? '—' }}</span>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @endif

            {{-- Modals --}}
            @foreach($employees as $emp)
                @include('employees::livewire.employees.components.view-employee-modal', ['employee' => $emp])
            @endforeach

        @else
            {{-- Empty State (مثل Companies) --}}
            <x-ui.card>
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-users text-gray-400 text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ tr('No employees found') }}
                    </h3>
                    <p class="text-sm text-gray-500 mb-6">
                        @if(($search ?? '') !== '' || ($departmentId ?? 'all') !== 'all' || ($jobTitleId ?? 'all') !== 'all' || ($status ?? 'all') !== 'all')
                            {{ tr('Try adjusting your search or filters') }}
                        @else
                            {{ tr('Get started by creating your first employee') }}
                        @endif
                    </p>
                </div>
            </x-ui.card>
        @endif

    </div>

    {{-- Deactivate User Modal --}}
    @if($showDeactivateModal)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-data x-cloak>
            {{-- Modal Content --}}
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden animate-fade-in-up">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">
                        {{ tr('Deactivate Employee') }}
                    </h3>
                    <button wire:click="closeDeactivateModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Employee Info (Read Only) --}}
                    @if($selectedEmployee)
                        <div class="bg-blue-50/40 rounded-xl p-5 border border-blue-100">
                            <h4 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 border-b border-blue-100 pb-2 flex items-center gap-2">
                                <i class="fas fa-info-circle"></i>
                                {{ tr('Employee Information') }}
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Name') }}</span>
                                    <span class="block text-sm font-semibold text-gray-900 truncate" title="{{ $selectedEmployee->name_ar ?: $selectedEmployee->name_en }}">
                                        {{ $selectedEmployee->name_ar ?: $selectedEmployee->name_en }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Employee No') }}</span>
                                    <span class="block text-sm font-medium text-gray-700 font-mono">
                                        {{ $selectedEmployee->employee_no }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Department') }}</span>
                                    <span class="block text-sm font-medium text-gray-700 truncate">
                                        {{ $selectedEmployee->department?->name ?? '-' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Job Title') }}</span>
                                    <span class="block text-sm font-medium text-gray-700 truncate">
                                        {{ $selectedEmployee->jobTitle?->name ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Warning / Info Tooltip --}}
                    <div class="relative mb-6" x-data="{ showTooltip: false }">
                        <button 
                            type="button" 
                            @mouseenter="showTooltip = true" 
                            @mouseleave="showTooltip = false"
                            class="flex items-center gap-2 text-xs font-bold text-yellow-600 hover:text-yellow-700 transition-colors cursor-help focus:outline-none"
                        >
                            <i class="fas fa-info-circle"></i>
                            <span class="border-b border-dashed border-yellow-300">{{ tr('View automatic actions upon deactivation') }}</span>
                        </button>
                        
                        <div 
                            x-show="showTooltip" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="absolute z-50 top-full start-0 mt-2 w-72 bg-white border border-gray-100 rounded-xl shadow-xl p-4 text-xs text-gray-600 pointer-events-none"
                            style="display: none;"
                        >
                            <h5 class="font-bold text-gray-900 mb-2 flex items-center gap-2">
                                <i class="fas fa-magic text-yellow-500"></i>
                                {{ tr('System will automatically:') }}
                            </h5>
                            <ul class="space-y-1.5 list-disc list-inside marker:text-yellow-500">
                                <li>{{ tr('Suspend Monthly Salary') }}</li>
                                <li>{{ tr('Suspend Vacation Accruals') }}</li>
                                <li>{{ tr('Revoke System Access') }}</li>
                                <li>{{ tr('Hide from Active Attendance') }}</li>
                                <li>{{ tr('Deduct Days from Diary') }}</li>
                                <li>{{ tr('Notify Employee & Admin') }}</li>
                            </ul>
                            {{-- Arrow --}}
                            <div class="absolute -top-1.5 start-6 w-3 h-3 bg-white border-t border-s border-gray-100 transform rotate-45"></div>
                        </div>
                    </div>

                    {{-- Form --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-1">
                            <x-ui.select 
                                :label="tr('Deactivation Reason')" 
                                model="deactivateReason"
                                error="deactivateReason"
                                :required="true"
                            >
                                <option value="">{{ tr('Select Reason') }}</option>
                                <option value="LONG_UNPAID_LEAVE">{{ tr('Long Unpaid Leave') }}</option>
                                <option value="ADMINISTRATIVE_INVESTIGATION">{{ tr('Administrative Investigation') }}</option>
                                <option value="ADMINISTRATIVE_SUSPENSION">{{ tr('Administrative Suspension Order') }}</option>
                                <option value="EXTERNAL_TRAINING">{{ tr('Long-term External Training') }}</option>
                                <option value="MEDICAL_CONDITION">{{ tr('Medical Condition') }}</option>
                            </x-ui.select>
                        </div>
                        
                        <div class="sm:col-span-1">
                            <x-ui.company-date-picker
                                model="deactivateDate"
                                :label="tr('Effective Date')"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <x-ui.textarea 
                                :label="tr('Notes')" 
                                wire:model="deactivateNotes" 
                                :placeholder="tr('Additional comments or details about the deactivation...')" 
                                rows="3" 
                            />
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100">
                    <x-ui.secondary-button wire:click="closeDeactivateModal">
                        {{ tr('Cancel') }}
                    </x-ui.secondary-button>
                    
                    <x-ui.primary-button wire:click="deactivateEmployee" wire:loading.attr="disabled" class="bg-red-600 hover:bg-red-700 focus:ring-red-500 active:bg-red-800">
                        <i class="fas fa-ban me-2"></i>
                        <span wire:loading.remove>{{ tr('Confirm Deactivation') }}</span>
                        <span wire:loading>{{ tr('Processing...') }}</span>
                    </x-ui.primary-button>
                </div>
            </div>
        </div>
    @endif

    {{-- Termination / End of Service Modal --}}
    @if($showTerminationModal)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-data="{
            dueSalary: @entangle('dueSalary').live,
            dueVacation: @entangle('dueVacation').live,
            dueOthers: @entangle('dueOthers').live,
            get totalDues() {
                return (parseFloat(this.dueSalary) || 0) + (parseFloat(this.dueVacation) || 0) + (parseFloat(this.dueOthers) || 0);
            }
        }" x-cloak>
            
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden animate-fade-in-up flex flex-col max-h-[90vh]">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 flex-shrink-0">
                    <h3 class="text-lg font-bold text-gray-900">
                        {{ tr('End of Service / Start Offboarding') }}
                    </h3>
                    <button wire:click="closeTerminationModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar">
                    {{-- Employee Info (Read Only) --}}
                    @if($selectedEmployee)
                        <div class="bg-blue-50/40 rounded-xl p-5 border border-blue-100">
                            <h4 class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-4 border-b border-blue-100 pb-2 flex items-center gap-2">
                                <i class="fas fa-user-circle"></i>
                                {{ tr('Employee Information') }}
                            </h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Name') }}</span>
                                    <span class="block text-sm font-semibold text-gray-900 truncate">{{ $selectedEmployee->name_ar ?: $selectedEmployee->name_en }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Employee No') }}</span>
                                    <span class="block text-sm font-medium text-gray-700 font-mono">{{ $selectedEmployee->employee_no }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Department') }}</span>
                                    <span class="block text-sm font-medium text-gray-700 truncate">{{ $selectedEmployee->department?->name ?? '-' }}</span>
                                </div>
                                <div>
                                    <span class="block text-xs text-gray-500 mb-1">{{ tr('Job Title') }}</span>
                                    <span class="block text-sm font-medium text-gray-700 truncate">{{ $selectedEmployee->jobTitle?->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Termination Details --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div class="sm:col-span-1">
                            <x-ui.select 
                                :label="tr('Termination Type')" 
                                model="terminationType"
                                error="terminationType"
                                :required="true"
                            >
                                <option value="">{{ tr('Select Type') }}</option>
                                <option value="RESIGNATION">{{ tr('Resignation') }}</option>
                                <option value="TERMINATION">{{ tr('Termination') }}</option>
                                <option value="RETIREMENT">{{ tr('Retirement') }}</option>
                                <option value="CONTRACT_END">{{ tr('Contract End') }}</option>
                                <option value="DEATH">{{ tr('Death') }}</option>
                            </x-ui.select>
                        </div>
                        
                        <div class="sm:col-span-1">
                            <x-ui.company-date-picker
                                model="terminationDate"
                                :label="tr('Termination Date')"
                            />
                        </div>

                        <div class="sm:col-span-2">
                            <x-ui.textarea 
                                :label="tr('Termination Reason')" 
                                wire:model="terminationReason" 
                                :placeholder="tr('Reason for termination...')" 
                                rows="2" 
                            />
                        </div>
                    </div>

                    {{-- Financial Settlements --}}
                    <div class="border-t border-gray-100 pt-5">
                        <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-calculator text-[color:var(--brand-via)]"></i>
                            {{ tr('Financial Settlements') }}
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <x-ui.input 
                                type="number" 
                                :label="tr('Salary Due')" 
                                x-model="dueSalary"
                                step="0.01"
                                placeholder="0.00"
                            />
                            
                            <x-ui.input 
                                type="number" 
                                :label="tr('Vacation Due')" 
                                x-model="dueVacation"
                                step="0.01"
                                placeholder="0.00"
                            />

                            <x-ui.input 
                                type="number" 
                                :label="tr('Other Dues')" 
                                x-model="dueOthers"
                                step="0.01"
                                placeholder="0.00"
                            />
                        </div>

                        {{-- Total Calculation --}}
                        <div class="mt-4 bg-gray-50 rounded-xl p-4 flex items-center justify-between border border-gray-200">
                            <span class="text-sm font-bold text-gray-700">{{ tr('Total Dues') }}</span>
                            <span class="text-xl font-extrabold text-[color:var(--brand-via)]" x-text="totalDues.toFixed(2)">0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100 flex-shrink-0">
                    <x-ui.secondary-button wire:click="closeTerminationModal">
                        {{ tr('Cancel') }}
                    </x-ui.secondary-button>
                    
                    <x-ui.primary-button wire:click="terminateEmployee" wire:loading.attr="disabled" class="bg-red-600 hover:bg-red-700 focus:ring-red-500 active:bg-red-800">
                        <i class="fas fa-sign-out-alt me-2"></i>
                        <span wire:loading.remove>{{ tr('Start Offboarding') }}</span>
                        <span wire:loading>{{ tr('Processing...') }}</span>
                    </x-ui.primary-button>
                </div>
            </div>
        </div>
    @endif
    {{-- Import Employees Modal --}}
    @if($showImportModal)
        <div 
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            style="z-index: 9999;"
            x-data="{ isDragging: false }"
        >
            <div 
                class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col border border-gray-100"
                @click.away="$wire.closeImportModal()"
            >
                {{-- Header --}}
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-[color:var(--brand-from)]/5 via-white to-[color:var(--brand-to)]/5">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[color:var(--brand-from)] to-[color:var(--brand-to)] flex items-center justify-center shadow-lg shadow-[color:var(--brand-via)]/20">
                                <i class="fas fa-file-import text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ tr('Import Employees') }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">{{ tr('Follow steps to import employee database via Excel/CSV') }}</p>
                            </div>
                        </div>
                        <button 
                            wire:click="closeImportModal"
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all duration-200"
                        >
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Body --}}
                <div class="p-6 overflow-y-auto custom-scrollbar space-y-8">
                    
                    {{-- Step 1: Download Templates --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center">1</span>
                            <h4 class="font-bold text-gray-800">{{ tr('Download Templates & Data Reference') }}</h4>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 ms-11">
                            <button 
                                wire:click="downloadTemplate"
                                class="flex flex-col items-center gap-3 p-4 rounded-2xl border border-dashed border-[color:var(--brand-via)]/30 bg-[color:var(--brand-via)]/5 hover:bg-[color:var(--brand-via)]/10 hover:border-[color:var(--brand-via)] transition-all group"
                            >
                                <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-file-excel text-[color:var(--brand-via)] text-xl"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-700 text-center">{{ tr('Employee Template') }}</span>
                            </button>
 
                            <button 
                                wire:click="downloadDepartmentsCodes"
                                class="flex flex-col items-center gap-3 p-4 rounded-2xl border border-dashed border-blue-200 bg-blue-50/30 hover:bg-blue-50 hover:border-blue-400 transition-all group"
                            >
                                <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-sitemap text-blue-600 text-xl"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-700 text-center">{{ tr('Departments Codes') }}</span>
                            </button>
 
                            <button 
                                wire:click="downloadJobTitlesCodes"
                                class="flex flex-col items-center gap-3 p-4 rounded-2xl border border-dashed border-teal-200 bg-teal-50/30 hover:bg-teal-50 hover:border-teal-400 transition-all group"
                            >
                                <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-briefcase text-teal-600 text-xl"></i>
                                </div>
                                <span class="text-xs font-bold text-gray-700 text-center">{{ tr('Job Titles Codes') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Step 2: Upload File --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-sm flex items-center justify-center">2</span>
                            <h4 class="font-bold text-gray-800">{{ tr('Upload Completed File') }}</h4>
                        </div>
                        
                        <div class="ms-11 relative">
                            <label 
                                class="relative flex flex-col items-center justify-center w-full min-h-[180px] p-6 rounded-3xl border-2 border-dashed transition-all duration-300 cursor-pointer"
                                :class="isDragging ? 'border-[color:var(--brand-via)] bg-[color:var(--brand-via)]/5' : 'border-gray-200 bg-gray-50/50 hover:bg-gray-50' "
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="isDragging = false"
                            >
                                <input type="file" wire:model="importFile" class="hidden" accept=".csv" />
                                
                                <div class="flex flex-col items-center text-center">
                                    @if($importFile)
                                        <div class="w-20 h-20 rounded-2xl bg-[color:var(--brand-via)]/10 flex items-center justify-center mb-4 border border-[color:var(--brand-via)]/20 shadow-sm">
                                            <i class="fas fa-file-csv text-[color:var(--brand-via)] text-3xl"></i>
                                        </div>
                                        <span class="text-sm font-bold text-gray-900">{{ $importFile->getClientOriginalName() }}</span>
                                        <span class="text-xs text-gray-500 mt-1.5 px-3 py-1 bg-gray-100 rounded-full font-medium">{{ number_format($importFile->getSize() / 1024, 2) }} KB</span>
                                        <button type="button" class="mt-4 text-xs font-bold text-red-600 hover:text-red-700 transition-colors flex items-center gap-1.5" @click.prevent="$wire.set('importFile', null)">
                                            <i class="fas fa-trash-alt"></i>
                                            {{ tr('Remove file') }}
                                        </button>
                                    @else
                                        <div class="w-20 h-20 rounded-2xl bg-white border border-gray-100 flex items-center justify-center shadow-sm mb-4 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-cloud-upload-alt text-[color:var(--brand-via)] text-3xl"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-900">{{ tr('Drop your file here or click to browse') }}</p>
                                        <p class="text-xs text-gray-500 mt-1.5">{{ tr('Only .csv files are supported') }}</p>
                                    @endif
                                </div>
 
                                {{-- Loading for file upload --}}
                                <div wire:loading wire:target="importFile" class="absolute inset-0 bg-white/90 backdrop-blur-sm rounded-3xl flex flex-col items-center justify-center z-10 transition-all">
                                    <div class="w-10 h-10 rounded-full border-4 border-[color:var(--brand-via)]/20 border-t-[color:var(--brand-via)] animate-spin"></div>
                                    <span class="text-sm font-bold text-[color:var(--brand-via)] mt-3">{{ tr('Uploading...') }}</span>
                                </div>

                                {{-- Loading for import process --}}
                                <div wire:loading wire:target="import" class="absolute inset-0 bg-white/90 backdrop-blur-sm rounded-3xl flex flex-col items-center justify-center z-20 transition-all">
                                    <div class="w-12 h-12 rounded-full border-4 border-[color:var(--brand-via)]/20 border-t-[color:var(--brand-via)] animate-spin mb-4"></div>
                                    <div class="flex flex-col items-center gap-1 px-6 text-center">
                                        <span class="text-base font-bold text-gray-900">{{ tr('Processing Data...') }}</span>
                                        <span class="text-xs text-gray-500">{{ tr('Please wait, this may take a moment.') }}</span>
                                    </div>
                                </div>
                            </label>
 
                            @error('importFile')
                                <p class="mt-3 text-xs font-medium text-red-600 bg-red-50 border border-red-100 rounded-lg px-3 py-2 flex items-center gap-2">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>

                    {{-- Validation Errors --}}
                    @if(!empty($importValidationErrors))
                        <div class="ms-11 rounded-2xl bg-red-50 border border-red-100 overflow-hidden animate-shake">
                            <div class="px-4 py-3 bg-red-100/50 flex items-center justify-between">
                                <span class="text-xs font-bold text-red-800 flex items-center gap-2">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    {{ tr('Data Validation Issues') }}
                                </span>
                                <span class="bg-red-200 text-red-800 text-[10px] font-extrabold px-1.5 py-0.5 rounded">
                                    {{ count($importValidationErrors) }} {{ tr('Issues') }}
                                </span>
                            </div>
                            <ul class="p-4 space-y-1.5 max-h-[160px] overflow-y-auto custom-scrollbar">
                                @foreach($importValidationErrors as $error)
                                    <li class="text-xs text-red-700 flex items-start gap-2">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-red-400 mt-1 flex-shrink-0"></span>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex items-center justify-end gap-3">
                    <x-ui.secondary-button wire:click="closeImportModal">
                        {{ tr('Cancel') }}
                    </x-ui.secondary-button>
                    
                    <x-ui.primary-button 
                        wire:click="import" 
                        wire:loading.attr="disabled"
                        :disabled="!$importFile || $isImporting"
                        class="bg-[color:var(--brand-via)] hover:bg-[color:var(--brand-to)] shadow-lg shadow-[color:var(--brand-via)]/20 px-8"
                    >
                        <i class="fas fa-check-circle me-2" wire:loading.remove wire:target="import"></i>
                        <span wire:loading wire:target="import" class="w-4 h-4 border-2 border-white/30 border-t-white animate-spin rounded-full me-2 inline-block"></span>
                        
                        <span wire:loading.remove wire:target="import">{{ tr('Verify & Import') }}</span>
                        <span wire:loading wire:target="import">{{ tr('Processing...') }}</span>
                    </x-ui.primary-button>
                </div>
            </div>
        </div>
    @endif

    {{-- ✅ Export Modal --}}
    @if($showExportModal)
        <div class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-gray-900/60 backdrop-blur-md">
            <div 
                class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-3xl overflow-hidden border border-white/20 animate-in fade-in zoom-in duration-300 transform"
                x-on:click.away="$wire.showExportModal = false"
            >
                {{-- Header --}}
                <div class="relative bg-white border-b border-gray-100 p-6 text-gray-900 overflow-hidden">
                    <div class="relative flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center text-[color:var(--brand-via)] shadow-sm border border-gray-100">
                                <i class="fas fa-file-export text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-black tracking-tight text-gray-900">{{ tr('Export Employees Data') }}</h3>
                                <p class="text-gray-500 text-xs mt-0.5 font-medium">{{ tr('Choose format and fields for your report') }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('showExportModal', false)" @click.stop class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6 max-h-[60vh] overflow-y-auto custom-scrollbar">
                    {{-- 1. Format Selection --}}
                    <div class="space-y-3">
                        <label class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                            {{ tr('1. Export Format') }}
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer group">
                                <input type="radio" wire:model.live="exportFormat" value="excel" class="peer sr-only">
                                <div class="p-3 rounded-2xl border-2 border-gray-100 bg-gray-50/50 flex items-center gap-3 transition-all duration-300 peer-checked:border-[color:var(--brand-via)] peer-checked:bg-[color:var(--brand-via)]/[0.03] group-hover:bg-white group-hover:shadow-md">
                                    <div class="w-10 h-10 bg-green-50 rounded-xl flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform duration-300">
                                        <i class="fas fa-file-excel"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-bold text-gray-900">{{ tr('Excel (CSV)') }}</div>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border-2 border-gray-200 flex items-center justify-center peer-checked:border-[color:var(--brand-via)] peer-checked:bg-[color:var(--brand-via)] transition-all">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative cursor-pointer group">
                                <input type="radio" wire:model.live="exportFormat" value="pdf" class="peer sr-only">
                                <div class="p-3 rounded-2xl border-2 border-gray-100 bg-gray-50/50 flex items-center gap-3 transition-all duration-300 peer-checked:border-[color:var(--brand-via)] peer-checked:bg-[color:var(--brand-via)]/[0.03] group-hover:bg-white group-hover:shadow-md">
                                    <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-red-600 group-hover:scale-110 transition-transform duration-300">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-bold text-gray-900">{{ tr('PDF Document') }}</div>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border-2 border-gray-200 flex items-center justify-center peer-checked:border-[color:var(--brand-via)] peer-checked:bg-[color:var(--brand-via)] transition-all">
                                        <div class="w-1.5 h-1.5 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- 2. Scope Selection --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-gray-400 uppercase tracking-widest flex items-center gap-2">
                                {{ tr('2. Fields Selection') }}
                            </label>
                            
                            @if($exportScope === 'custom')
                                <div class="flex items-center gap-3">
                                    <button type="button" wire:click="$set('selectedFields', @js(array_keys($this->availableFields)))" class="text-[10px] font-black text-[color:var(--brand-via)] hover:opacity-70">{{ tr('Select All') }}</button>
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <button type="button" wire:click="$set('selectedFields', [])" class="text-[10px] font-black text-gray-400 hover:text-red-500">{{ tr('Clear') }}</button>
                                </div>
                            @endif
                        </div>

                        <div class="flex gap-2 p-1 bg-gray-100/50 rounded-xl border border-gray-100">
                            <button 
                                type="button"
                                wire:click="$set('exportScope', 'all')"
                                class="flex-1 py-1.5 px-4 rounded-lg text-xs font-bold transition-all duration-300 {{ $exportScope === 'all' ? 'bg-white text-[color:var(--brand-via)] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                            >
                                {{ tr('All Fields') }}
                            </button>
                            <button 
                                type="button"
                                wire:click="$set('exportScope', 'custom')"
                                class="flex-1 py-1.5 px-4 rounded-lg text-xs font-bold transition-all duration-300 {{ $exportScope === 'custom' ? 'bg-white text-[color:var(--brand-via)] shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                            >
                                {{ tr('Customize Fields') }}
                            </button>
                        </div>

                        {{-- Custom Fields Checkboxes --}}
                        @if($exportScope === 'custom')
                            <div class="animate-in slide-in-from-top-2 duration-300 border border-gray-100 rounded-2xl overflow-hidden bg-gray-50/20">
                                <div class="max-h-[220px] overflow-y-auto custom-scrollbar p-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-1">
                                    @foreach($this->availableFields as $key => $label)
                                        <label wire:key="export-field-{{ $key }}" class="flex items-center gap-2.5 cursor-pointer group py-1.5 border-b border-gray-50/50 last:border-0 hover:bg-white px-2 rounded-lg transition-all">
                                            <div class="relative w-3.5 h-3.5 flex items-center justify-center flex-shrink-0">
                                                <input type="checkbox" wire:model="selectedFields" value="{{ $key }}" class="peer sr-only">
                                                <div class="absolute inset-0 border border-gray-300 rounded transition-all peer-checked:border-[color:var(--brand-via)] peer-checked:bg-[color:var(--brand-via)]"></div>
                                                <i class="fas fa-check text-[7px] text-white opacity-0 peer-checked:opacity-100 transition-opacity"></i>
                                            </div>
                                            <span class="text-[11px] font-bold text-gray-600 group-hover:text-gray-900 transition-colors truncate">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between gap-3">
                    <x-ui.secondary-button wire:click="$set('showExportModal', false)" class="!rounded-xl !py-2">
                        {{ tr('Cancel') }}
                    </x-ui.secondary-button>
                    
                    <div class="flex items-center gap-3">
                        <x-ui.primary-button 
                            wire:click="export" 
                            wire:loading.attr="disabled"
                            class="bg-[color:var(--brand-via)] hover:bg-[color:var(--brand-to)] shadow-lg shadow-[color:var(--brand-via)]/10 px-8 !rounded-xl !py-2.5"
                        >
                            <span wire:loading.remove wire:target="export" class="flex items-center gap-2 font-bold text-sm">
                                <i class="fas fa-download"></i>
                                <span>{{ tr('Download Now') }}</span>
                            </span>

                            <span wire:loading wire:target="export" class="flex items-center gap-2 text-sm">
                                <div class="w-3.5 h-3.5 border-2 border-white/30 border-t-white animate-spin rounded-full"></div>
                                <span>{{ tr('Generating...') }}</span>
                            </span>
                        </x-ui.primary-button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>





