<div class="space-y-6">
    {{-- Top Section: Grid with Side-by-Side layout --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        {{-- Right Side: Personal Photo --}}
        <div>
            <x-ui.image
                label="{{ tr('Personal Photo') }}"
                wire:model="photo"
                error="photo"
                {{-- Removed custom previewSize to ensure image displays correctly (default size usually works best) --}}
                :existingImage="$existing_photo ? asset('storage/' . $existing_photo->file_path) : null"
            />
        </div>

        {{-- Left Side: National ID & Qualification --}}
        <div class="space-y-4">
            {{-- National ID Photo --}}
            <div>
                <x-ui.image
                    label="{{ tr('National ID Photo') }}"
                    wire:model="national_id_photo"
                    error="national_id_photo"
                    :existingImage="$existing_national_id_photo ? asset('storage/' . $existing_national_id_photo->file_path) : null"
                />
            </div>

            {{-- Qualification --}}
            <div>
                 <x-ui.file
                    label="{{ tr('Qualification') }}"
                    wire:model="qualification"
                    error="qualification"
                    accept=".pdf,.jpg,.png,.jpeg"
                    :existingFile="$existing_qualification ? ['original_name' => $existing_qualification->title ?? basename($existing_qualification->file_path), 'url' => asset('storage/' . $existing_qualification->file_path)] : null"
                />
            </div>
        </div>
    </div>

    <div class="h-px bg-gray-100"></div>

    {{-- Bottom Section: Multiple Files --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gray-50/50 rounded-lg p-2 border border-gray-100">
            <x-ui.multiple-files
                label="{{ tr('Certificates') }}"
                wire:model="certificates"
                error="certificates"
                :existingFiles="$existing_certificates"
            />
        </div>
        
        <div class="bg-gray-50/50 rounded-lg p-2 border border-gray-100">
            <x-ui.multiple-files
                label="{{ tr('Family Documents') }}"
                wire:model="family_documents"
                error="family_documents"
                :existingFiles="$existing_family_documents"
            />
        </div>
        
        <div class="bg-gray-50/50 rounded-lg p-2 border border-gray-100">
            <x-ui.multiple-files
                label="{{ tr('Other Documents') }}"
                wire:model="other_documents"
                error="other_documents"
                :existingFiles="$existing_other_documents"
            />
        </div>
    </div>
</div>




