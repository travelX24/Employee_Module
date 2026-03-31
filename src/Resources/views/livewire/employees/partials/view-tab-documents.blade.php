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

        $qualificationName = $qualification
            ? ($qualification->title ?: basename($qualification->file_path))
            : null;
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Personal Photo --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ tr('Personal Photo') }}
            </label>

            @if($personalPhoto)
                <div class="relative group overflow-hidden rounded-xl border border-gray-200">
                    <img
                        src="{{ asset('storage/' . $personalPhoto->file_path) }}"
                        alt="{{ tr('Personal Photo') }}"
                        class="w-full h-48 object-cover"
                    >
                    <a
                        href="{{ asset('storage/' . $personalPhoto->file_path) }}"
                        target="_blank"
                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
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
        <div class="md:col-span-2 bg-white rounded-2xl border border-gray-200 p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ tr('National ID Photo') }}
            </label>

            @if($nationalIdPhoto)
                <div class="relative group overflow-hidden rounded-xl border border-gray-200">
                    <img
                        src="{{ asset('storage/' . $nationalIdPhoto->file_path) }}"
                        alt="{{ tr('National ID Photo') }}"
                        class="w-full h-48 object-cover"
                    >
                    <a
                        href="{{ asset('storage/' . $nationalIdPhoto->file_path) }}"
                        target="_blank"
                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
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
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ tr('Qualification') }}
            </label>

            @if($qualification)
                <a
                    href="{{ asset('storage/' . $qualification->file_path) }}"
                    target="_blank"
                    title="{{ $qualificationName }}"
                    class="flex items-center gap-3 px-4 py-3 bg-blue-50 rounded-xl border border-blue-200 text-blue-700 hover:bg-blue-100 transition-colors overflow-hidden"
                >
                    <i class="fas fa-file-pdf shrink-0"></i>
                    <span class="truncate min-w-0">
                        {{ $qualificationName }}
                    </span>
                </a>
            @else
                <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-200 text-gray-400">
                    {{ tr('No document') }}
                </div>
            @endif
        </div>

        {{-- Certificates --}}
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ tr('Certificates') }}
            </label>

            @if($certificates->count() > 0)
                <div class="space-y-2">
                    @foreach($certificates as $cert)
                        @php
                            $certName = $cert->title ?: basename($cert->file_path);
                        @endphp

                        <a
                            href="{{ asset('storage/' . $cert->file_path) }}"
                            target="_blank"
                            title="{{ $certName }}"
                            class="flex items-center gap-3 px-4 py-2.5 bg-green-50 rounded-xl border border-green-200 text-green-700 hover:bg-green-100 transition-colors text-sm overflow-hidden"
                        >
                            <i class="fas fa-file shrink-0"></i>
                            <span class="truncate min-w-0">
                                {{ $certName }}
                            </span>
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
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                {{ tr('Family Documents') }}
            </label>

            @if($familyDocuments->count() > 0)
                <div class="space-y-2">
                    @foreach($familyDocuments as $doc)
                        @php
                            $familyDocName = $doc->title ?: basename($doc->file_path);
                        @endphp

                        <a
                            href="{{ asset('storage/' . $doc->file_path) }}"
                            target="_blank"
                            title="{{ $familyDocName }}"
                            class="flex items-center gap-3 px-4 py-2.5 bg-purple-50 rounded-xl border border-purple-200 text-purple-700 hover:bg-purple-100 transition-colors text-sm overflow-hidden"
                        >
                            <i class="fas fa-file shrink-0"></i>
                            <span class="truncate min-w-0">
                                {{ $familyDocName }}
                            </span>
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
    <div class="bg-white rounded-2xl border border-gray-200 p-4">
        <label class="block text-sm font-semibold text-gray-700 mb-3">
            {{ tr('Other Documents') }}
        </label>

        @if($otherDocuments->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @foreach($otherDocuments as $doc)
                    @php
                        $otherDocName = $doc->title ?: basename($doc->file_path);
                    @endphp

                    <a
                        href="{{ asset('storage/' . $doc->file_path) }}"
                        target="_blank"
                        title="{{ $otherDocName }}"
                        class="flex items-center gap-3 px-4 py-2.5 bg-gray-50 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-100 transition-colors text-sm overflow-hidden"
                    >
                        <i class="fas fa-file shrink-0"></i>
                        <span class="truncate min-w-0">
                            {{ $otherDocName }}
                        </span>
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