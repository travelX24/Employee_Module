<div class="space-y-4 sm:space-y-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        {{-- Mobile --}}
        <x-ui.input 
            :label="tr('Mobile')" 
            wire:model="mobile" 
            value="{{ $mobile }}"
            error="mobile" 
            :required="true"
            placeholder="+966 5x xxx xxxx"
        />

        {{-- Alt Mobile --}}
        <x-ui.input 
            :label="tr('Alternative Mobile')" 
            wire:model="mobile_alt" 
            value="{{ $mobile_alt }}"
            error="mobile_alt"
            placeholder="+966 5x xxx xxxx"
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
            placeholder="+966 5x xxx xxxx"
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
        <x-ui.input 
            :label="tr('Relation')" 
            wire:model="emergency_contact_relation" 
            value="{{ $emergency_contact_relation }}"
            error="emergency_contact_relation" 
            :required="true"
            placeholder="{{ tr('Father, Mother, Brother, etc.') }}"
        />
    </div>
</div>



