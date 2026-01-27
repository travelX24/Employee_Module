@props(['employee'])

<div class="space-y-6">
    @php
        $documents = $employee->documents ?? collect();
        $personalPhoto = $documents->where('type', 'personal_photo')->first();
        $nationalIdPhoto = $documents->where('type', 'national_id_photo')->first();
        $qualification = $documents->where('type', 'qualification')->first();
        $certificates = $documents->where('type', 'certificates');
        $familyDocuments = $documents->where('type', 'family_documents');
        $otherDocuments = $documents->where('type', 'other_documents');
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Personal Photo --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Personal Photo') }}
            </label>
            @if($personalPhoto)
                <div class="relative group">
                    <img 
                        src="{{ asset('storage/' . $personalPhoto->file_path) }}" 
                        alt="{{ tr('Personal Photo') }}"
                        class="w-full h-48 object-cover rounded-xl border border-gray-200"
                    >
                    <a 
                        href="{{ asset('storage/' . $personalPhoto->file_path) }}" 
                        target="_blank"
                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex items-center justify-center"
                    >
                        <i class="fas fa-search-plus text-white text-2xl"></i>
                    </a>
                </div>
            @else
                <div class="w-full h-48 bg-gray-100 rounded-xl border border-gray-200 flex items-center justify-center">
                    <span class="text-gray-400">{{ tr('No photo') }}</span>
                </div>
            @endif
        </div>

        {{-- National ID Photo --}}
        <div class="md:col-span-2">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('National ID Photo') }}
            </label>
            @if($nationalIdPhoto)
                <div class="relative group">
                    <img 
                        src="{{ asset('storage/' . $nationalIdPhoto->file_path) }}" 
                        alt="{{ tr('National ID Photo') }}"
                        class="w-full h-48 object-cover rounded-xl border border-gray-200"
                    >
                    <a 
                        href="{{ asset('storage/' . $nationalIdPhoto->file_path) }}" 
                        target="_blank"
                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity rounded-xl flex items-center justify-center"
                    >
                        <i class="fas fa-search-plus text-white text-2xl"></i>
                    </a>
                </div>
            @else
                <div class="w-full h-48 bg-gray-100 rounded-xl border border-gray-200 flex items-center justify-center">
                    <span class="text-gray-400">{{ tr('No photo') }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Document Sections --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Qualification --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Qualification') }}
            </label>
            @if($qualification)
                <a 
                    href="{{ asset('storage/' . $qualification->file_path) }}" 
                    target="_blank"
                    class="block px-4 py-3 bg-blue-50 rounded-xl border border-blue-200 text-blue-700 hover:bg-blue-100 transition-colors"
                >
                    <i class="fas fa-file-pdf me-2"></i>
                    {{ $qualification->title ?: tr('View Document') }}
                </a>
            @else
                <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-400">
                    {{ tr('No document') }}
                </div>
            @endif
        </div>

        {{-- Certificates --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Certificates') }}
            </label>
            @if($certificates->count() > 0)
                <div class="space-y-2">
                    @foreach($certificates as $cert)
                        <a 
                            href="{{ asset('storage/' . $cert->file_path) }}" 
                            target="_blank"
                            class="block px-4 py-2 bg-green-50 rounded-xl border border-green-200 text-green-700 hover:bg-green-100 transition-colors text-sm"
                        >
                            <i class="fas fa-file me-2"></i>
                            {{ $cert->title ?: tr('Certificate') }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-400">
                    {{ tr('No documents') }}
                </div>
            @endif
        </div>

        {{-- Family Documents --}}
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Family Documents') }}
            </label>
            @if($familyDocuments->count() > 0)
                <div class="space-y-2">
                    @foreach($familyDocuments as $doc)
                        <a 
                            href="{{ asset('storage/' . $doc->file_path) }}" 
                            target="_blank"
                            class="block px-4 py-2 bg-purple-50 rounded-xl border border-purple-200 text-purple-700 hover:bg-purple-100 transition-colors text-sm"
                        >
                            <i class="fas fa-file me-2"></i>
                            {{ $doc->title ?: tr('Document') }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-400">
                    {{ tr('No documents') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Other Documents --}}
    @if($otherDocuments->count() > 0)
        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
                {{ tr('Other Documents') }}
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($otherDocuments as $doc)
                    <a 
                        href="{{ asset('storage/' . $doc->file_path) }}" 
                        target="_blank"
                        class="block px-4 py-2 bg-gray-50 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-100 transition-colors text-sm"
                    >
                        <i class="fas fa-file me-2"></i>
                        {{ $doc->title ?: tr('Document') }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>




