<div class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- Arabic Name --}}
        <x-ui.input 
            :label="tr('Arabic Name')" 
            wire:model="name_ar" 
            value="{{ $name_ar }}"
            error="name_ar" 
            :required="true"
        />

        {{-- English Name --}}
        <x-ui.input 
            :label="tr('English Name')" 
            wire:model="name_en" 
            value="{{ $name_en }}"
            error="name_en"
        />

      {{-- ID Type + ID Number --}}
    <div class="col-span-1 sm:col-span-2 lg:col-span-2">
        <div class="grid grid-cols-12 gap-3">
            <div class="col-span-12 sm:col-span-4">
                <x-ui.select
                    :label="tr('ID Type')"
                    model="national_id_type"
                    error="national_id_type"
                    :required="true"
                >
                    <option value="">{{ tr('Select ID Type') }}</option>
                    <option value="national_id" {{ ($national_id_type ?? '') === 'national_id' ? 'selected' : '' }}>{{ tr('National ID') }}</option>
                    <option value="iqama"       {{ ($national_id_type ?? '') === 'iqama' ? 'selected' : '' }}>{{ tr('Iqama') }}</option>
                    <option value="passport"    {{ ($national_id_type ?? '') === 'passport' ? 'selected' : '' }}>{{ tr('Passport') }}</option>
                    <option value="other"       {{ ($national_id_type ?? '') === 'other' ? 'selected' : '' }}>{{ tr('Other') }}</option>
                </x-ui.select>
            </div>

            <div class="col-span-12 sm:col-span-8">
                <x-ui.input
                    :label="tr('ID Number')"
                    wire:model="national_id"
                    value="{{ $national_id }}"
                    error="national_id"
                    :required="true"
                />
            </div>
        </div>
    </div>

        {{-- National ID Expiry --}}
        <x-ui.company-date-picker
            model="national_id_expiry"
            :label="tr('National ID Expiry')"
        />

    {{-- Nationality --}}
    <x-ui.select
        :label="tr('Nationality')"
        model="nationality"
        error="nationality"
        :required="true"
    >
        <option value="">{{ tr('Select Nationality') }}</option>

        @foreach(($nationalityOptions ?? []) as $opt)
            <option value="{{ $opt['value'] }}" {{ ($nationality ?? '') == $opt['value'] ? 'selected' : '' }}>
                {{ $opt['label'] }}
            </option>
        @endforeach
    </x-ui.select>

        {{-- Gender --}}
        <x-ui.select 
            :label="tr('Gender')" 
            model="gender" 
            error="gender" 
            :required="true"
        >
            <option value="">{{ tr('Select Gender') }}</option>
            <option value="male" {{ $gender == 'male' ? 'selected' : '' }}>{{ tr('Male') }}</option>
            <option value="female" {{ $gender == 'female' ? 'selected' : '' }}>{{ tr('Female') }}</option>
        </x-ui.select>

        {{-- Birth Date --}}
        <x-ui.company-date-picker
            model="birth_date"
            :label="tr('Birth Date')"
        />

        {{-- Birth Place --}}
        <x-ui.input 
            :label="tr('Birth Place')" 
            wire:model="birth_place" 
            value="{{ $birth_place }}"
            error="birth_place" 
            :required="true"
        />

        {{-- Social Status --}}
        <x-ui.select 
            :label="tr('Social Status')" 
            model="social_status" 
            error="social_status" 
            :required="true"
        >
            <option value="">{{ tr('Select Social Status') }}</option>
            <option value="single" {{ $social_status == 'single' ? 'selected' : '' }}>{{ tr('Single') }}</option>
            <option value="married" {{ $social_status == 'married' ? 'selected' : '' }}>{{ tr('Married') }}</option>
        </x-ui.select>

        {{-- Children Count --}}
        <x-ui.input 
            type="number"
            :label="tr('Children Count')" 
            wire:model="children_count" 
            value="{{ $children_count }}"
            error="children_count"
            min="0"
            placeholder="0"
        />
    </div>
</div>



