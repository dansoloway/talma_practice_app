@props(['terms', 'locale' => null])

@if($terms)
    @php
        $localized = $terms->localized($locale);
    @endphp
    <div id="termsModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" onclick="if (event.target === this) closeTermsModal()">
        <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
            <div class="flex justify-between items-center p-4 border-b shrink-0">
                <h3 class="text-xl font-bold text-gray-900">{{ $localized['title'] }}</h3>
                <button type="button" onclick="closeTermsModal()" class="p-2 text-gray-500 hover:text-gray-700 rounded" aria-label="{{ __('parent-signup.terms_modal.close') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $localized['content'] }}</div>
            <div class="p-4 border-t shrink-0 flex justify-between items-center text-sm text-gray-500">
                <span>{{ __('parent-signup.terms_modal.version', ['version' => $terms->version, 'date' => $terms->updated_at->format('M j, Y')]) }}</span>
                <button type="button" onclick="closeTermsModal()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium">{{ __('parent-signup.terms_modal.close') }}</button>
            </div>
        </div>
    </div>

    @once
        @push('scripts')
        <script>
            function openTermsModal() {
                document.getElementById('termsModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
            function closeTermsModal() {
                document.getElementById('termsModal').classList.add('hidden');
                document.body.style.overflow = '';
            }
        </script>
        @endpush
    @endonce
@endif
