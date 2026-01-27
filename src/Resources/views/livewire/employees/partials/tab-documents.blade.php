<div class="space-y-4 sm:space-y-6">
    <div class="space-y-4">
        {{-- Top Section: Personal Photo vs Stacked Files --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
            {{-- Column 1: Personal Photo --}}
            <div class="md:col-span-1">
                <x-ui.image
                    :label="tr('Personal Photo')"
                    name="photo"
                    wire:model="photo"
                    target="photo"
                    :file="$photo"
                    accept="image/*"
                    :hint="tr('JPG/PNG — max 2MB')"
                    :required="true"
                    :previewSize="160"
                />
            </div>

            {{-- Column 2: National ID + Qualification --}}
            <div class="md:col-span-2 flex flex-col gap-4">
                <x-ui.file
                    :label="tr('National ID Photo')"
                    name="national_id_photo"
                    wire:model="national_id_photo"
                    target="national_id_photo"
                    :file="$national_id_photo"
                    accept="image/*"
                    :hint="tr('JPG/PNG — max 2MB')"
                    :required="true"
                />

                <x-ui.file
                    :label="tr('Qualification')"
                    name="qualification"
                    wire:model="qualification"
                    target="qualification"
                    :file="$qualification"
                    accept=".pdf,.jpg,.jpeg,.png"
                    :hint="tr('PDF/JPG/PNG — max 5MB')"
                />
            </div>
        </div>

        {{-- Bottom Section: Multiple Documents --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.multiple-files
                :label="tr('Certificates')"
                name="certificates"
                wire:model="certificates"
                target="certificates"
                :files="$certificates"
                accept=".pdf,.jpg,.jpeg,.png"
                :hint="tr('PDF/JPG/PNG — max 5MB each')"
            />

            <x-ui.multiple-files
                :label="tr('Family Documents')"
                name="family_documents"
                wire:model="family_documents"
                target="family_documents"
                :files="$family_documents"
                accept=".pdf,.jpg,.jpeg,.png"
                :hint="tr('PDF/JPG/PNG — max 5MB each')"
            />

            <x-ui.multiple-files
                :label="tr('Other Documents')"
                name="other_documents"
                wire:model="other_documents"
                target="other_documents"
                :files="$other_documents"
                accept=".pdf,.jpg,.jpeg,.png"
                :hint="tr('PDF/JPG/PNG — max 5MB each')"
            />
        </div>
    </div>

    <div class="rounded-xl sm:rounded-2xl border bg-gray-50 p-3 sm:p-4 text-[11px] sm:text-[12px] text-gray-600">
        <div class="font-bold text-gray-900 mb-1">{{ tr('Notes') }}</div>
        <ul class="list-disc ms-4 sm:ms-5 space-y-1">
            <li>{{ tr('Personal Photo and National ID Photo are required.') }}</li>
            <li>{{ tr('Other documents are optional but recommended.') }}</li>
            <li>{{ tr('Maximum file size: 2MB for photos, 5MB for other documents.') }}</li>
            <li>{{ tr('Accepted formats: JPG, PNG, PDF') }}</li>
        </ul>
    </div>
</div>



