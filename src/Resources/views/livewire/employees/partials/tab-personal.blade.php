<div class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Mobile --}}
     <x-ui.input 
        :label="tr('Mobile')" 
        wire:model="mobile" 
        value="{{ $mobile }}"
        error="mobile" 
        :required="true"
        :digitsOnly="true"
        :digitsMax="20"
        placeholder="9665XXXXXXXX"
    />

        {{-- Alt Mobile --}}
    <x-ui.input 
        :label="tr('Alternative Mobile')" 
        wire:model="mobile_alt" 
        value="{{ $mobile_alt }}"
        error="mobile_alt"
        :digitsOnly="true"
        :digitsMax="20"
        placeholder="9665XXXXXXXX"
    />

        {{-- Work Email --}}
        <x-ui.input 
            type="email"
            :label="tr('Work Email')" 
            wire:model="email_work" 
            value="{{ $email_work }}"
            error="email_work" 
            :required="true"
            placeholder="employee@company.com"
        />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Personal Email --}}
        <x-ui.input 
            type="email"
            :label="tr('Personal Email')" 
            wire:model="email_personal" 
            value="{{ $email_personal }}"
            error="email_personal"
            placeholder="personal@email.com"
        />

        {{-- City --}}
        <x-ui.input 
            :label="tr('City')" 
            wire:model="city" 
            value="{{ $city }}"
            error="city" 
            :required="true"
        />

        {{-- District --}}
        <x-ui.input 
            :label="tr('District')" 
            wire:model="district" 
            value="{{ $district }}"
            error="district" 
            :required="true"
        />
    </div>

    <div class="sm:col-span-2 lg:col-span-2">
        {{-- Address --}}
        <x-ui.input 
            :label="tr('Address')" 
            wire:model="address" 
            value="{{ $address }}"
            error="address" 
            :required="true"
            placeholder="{{ tr('Street, Building, Apartment') }}"
        />
    </div>

    <div class="h-px bg-gray-100"></div>

    <div class="text-sm font-bold text-gray-900 mb-2">{{ tr('Emergency Contact') }}</div>
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Emergency Phone --}}
     <x-ui.input 
        :label="tr('Emergency Phone')" 
        wire:model="emergency_contact_phone" 
        value="{{ $emergency_contact_phone }}"
        error="emergency_contact_phone" 
        :required="true"
        :digitsOnly="true"
        :digitsMax="20"
        placeholder="9665XXXXXXXX"
    />

        {{-- Emergency Name --}}
        <x-ui.input 
            :label="tr('Emergency Name')" 
            wire:model="emergency_contact_name" 
            value="{{ $emergency_contact_name }}"
            error="emergency_contact_name" 
            :required="true"
            placeholder="{{ tr('Full Name') }}"
        />

        {{-- Emergency Relation --}}
        <x-ui.select
            :label="tr('Relation')"
            model="emergency_contact_relation"
            error="emergency_contact_relation"
            :required="true"
        >
            <option value="">{{ tr('Select Relation') }}</option>

            <option value="أب"   {{ ($emergency_contact_relation ?? '') === 'أب' ? 'selected' : '' }}>أب</option>
            <option value="أم"   {{ ($emergency_contact_relation ?? '') === 'أم' ? 'selected' : '' }}>أم</option>
            <option value="أخ"   {{ ($emergency_contact_relation ?? '') === 'أخ' ? 'selected' : '' }}>أخ</option>
            <option value="أخت"  {{ ($emergency_contact_relation ?? '') === 'أخت' ? 'selected' : '' }}>أخت</option>
            <option value="زوج"  {{ ($emergency_contact_relation ?? '') === 'زوج' ? 'selected' : '' }}>زوج</option>
            <option value="زوجة" {{ ($emergency_contact_relation ?? '') === 'زوجة' ? 'selected' : '' }}>زوجة</option>
            <option value="ابن"  {{ ($emergency_contact_relation ?? '') === 'ابن' ? 'selected' : '' }}>ابن</option>
            <option value="بنت"  {{ ($emergency_contact_relation ?? '') === 'بنت' ? 'selected' : '' }}>بنت</option>

        </x-ui.select>
    </div>
</div>



