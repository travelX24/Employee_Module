<div class="space-y-6">
    {{-- Top Section: Grid with Side-by-Side layout --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        {{-- Right Side: Personal Photo --}}
        <div>
            <x-ui.image
                label="{{ tr('Personal Photo') }}"
                wire:model="photo"
                error="photo"
                maxKb="2048"
                accept="image/*"
                hint="{{ tr('JPG/PNG — max 2MB') }}"
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
                    maxKb="2048"
                    accept="image/*"
                    hint="{{ tr('JPG/PNG — max 2MB') }}"
                    :existingImage="$existing_national_id_photo ? asset('storage/' . $existing_national_id_photo->file_path) : null"
                />
            </div>

            {{-- Qualification --}}
            <div>
                 <x-ui.file
                    label="{{ tr('Qualification') }}"
                    wire:model="qualification"
                    error="qualification"
                    maxKb="5120"
                    accept=".pdf,.jpg,.png,.jpeg"
                    hint="{{ tr('PDF/JPG/PNG — max 5MB') }}"
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
                :label="tr('Certificates')"
                name="certificates"
                wire:model="certificates"
                target="certificates"
                :files="$certificates"
                error="certificates"
                maxKb="5120"
                accept=".pdf,.jpg,.jpeg,.png"
                hint="{{ tr('PDF/JPG/PNG — max 5MB each') }}"
                :existingFiles="$existing_certificates"
            />
        </div>
        
        <div class="bg-gray-50/50 rounded-lg p-2 border border-gray-100">
            <x-ui.multiple-files
                :label="tr('Family Documents')"
                name="family_documents"
                wire:model="family_documents"
                target="family_documents"
                :files="$family_documents"
                error="family_documents"
                maxKb="5120"
                accept=".pdf,.jpg,.jpeg,.png"
                hint="{{ tr('PDF/JPG/PNG — max 5MB each') }}"
                :existingFiles="$existing_family_documents"
            />
        </div>
        
        <div class="bg-gray-50/50 rounded-lg p-2 border border-gray-100">
            <x-ui.multiple-files
                :label="tr('Other Documents')"
                name="other_documents"
                wire:model="other_documents"
                target="other_documents"
                :files="$other_documents"
                error="other_documents"
                maxKb="5120"
                accept=".pdf,.jpg,.jpeg,.png"
                hint="{{ tr('PDF/JPG/PNG — max 5MB each') }}"
                :existingFiles="$existing_other_documents"
            />
        </div>
    </div>
</div>

<div class="mt-6 rounded-xl sm:rounded-2xl border bg-gray-50 p-3 sm:p-4 text-[11px] sm:text-[12px] text-gray-600">
    <div class="font-bold text-gray-900 mb-1">{{ tr('Notes') }}</div>
    <ul class="list-disc ms-4 sm:ms-5 space-y-1">
        <li>{{ tr('Maximum file size: 2MB for photos, 5MB for other documents.') }}</li>
        <li>{{ tr('Accepted formats: JPG, PNG, PDF') }}</li>
    </ul>
</div>




