<div class="space-y-6">
    @if($employee->statusLogs && $employee->statusLogs->count() > 0)
        <div class="relative">
            {{-- Vertical Line --}}
            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

            <div class="space-y-8">
                @foreach($employee->statusLogs->sortByDesc('created_at') as $log)
                    <div class="relative pl-12">
                        {{-- Icon Indicator --}}
                        <div @class([
                            'absolute left-0 w-8 h-8 rounded-full border-4 border-white flex items-center justify-center shadow-sm z-10',
                            'bg-red-500' => $log->action_type === 'TERMINATED',
                            'bg-amber-500' => $log->action_type === 'SUSPENDED',
                            'bg-green-500' => $log->action_type === 'ACTIVATED',
                        ])>
                            <i @class([
                                'fas text-white text-[10px]',
                                'fa-user-slash' => $log->action_type === 'TERMINATED',
                                'fa-user-clock' => $log->action_type === 'SUSPENDED',
                                'fa-user-check' => $log->action_type === 'ACTIVATED',
                            ])></i>
                        </div>

                        <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm hover:shadow-md transition-shadow group">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                        {{ tr($log->action_type) }}
                                        @if($log->action_type === 'TERMINATED')
                                            <span class="px-2 py-0.5 rounded-full bg-red-50 text-red-600 text-[10px] font-bold">{{ tr('Final Exit') }}</span>
                                        @endif
                                    </h4>
                                    <div class="flex items-center gap-3 mt-1">
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="far fa-calendar-alt text-gray-400"></i>
                                            {{ company_date($log->effective_date) }}
                                        </span>
                                        <span class="text-xs text-gray-500 flex items-center gap-1">
                                            <i class="far fa-clock text-gray-400"></i>
                                            {{ $log->created_at->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-50 rounded-xl border border-gray-100 group-hover:bg-indigo-50/30 group-hover:border-indigo-100 transition-colors">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center">
                                        <i class="fas fa-shield-alt text-indigo-600 text-[10px]"></i>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-wider">{{ tr('Performed By') }}</span>
                                        <span class="text-[11px] font-bold text-gray-700">{{ $log->performer?->name ?? tr('System') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @if($log->reason)
                                    <div class="bg-gray-50/50 rounded-xl p-3 border border-dashed border-gray-200">
                                        <div class="text-[10px] text-gray-400 font-bold mb-1 uppercase tracking-wider">{{ tr('Reason') }}</div>
                                        <div class="text-xs text-gray-700 leading-relaxed">{{ $log->reason }}</div>
                                    </div>
                                @endif

                                @if($log->notes)
                                    <div class="flex items-start gap-2 pt-1">
                                        <i class="fas fa-info-circle text-gray-400 mt-0.5 text-xs"></i>
                                        <div class="text-[11px] text-gray-500 italic">{{ $log->notes }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="flex flex-col items-center justify-center py-12 px-6 text-center bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
            <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center shadow-sm mb-4">
                <i class="fas fa-history text-gray-300 text-2xl"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-900 mb-1">{{ tr('No History Available') }}</h3>
            <p class="text-xs text-gray-500 max-w-xs">{{ tr('Status changes like deactivations or terminations will be logged here.') }}</p>
        </div>
    @endif
</div>
