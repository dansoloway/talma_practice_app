@if($terms || ($privacyPolicy ?? null))
    <div class="border-t pt-4 space-y-3">
        @if($terms)
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required
                       class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                       {{ old('terms_accepted') ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">
                    {{ __('parent-signup.terms_prefix') }}
                    <button type="button" onclick="openTermsModal()" class="text-blue-600 hover:underline font-medium">{{ __('parent-signup.terms_link') }}</button>
                </span>
            </label>

            <x-legal-document-modal :document="$terms" modal-id="termsModal" :locale="$locale ?? null" />
        @endif

        @if($privacyPolicy ?? null)
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" id="privacy_policy_read" name="privacy_policy_read" value="1" required
                       class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-400"
                       {{ old('privacy_policy_read') ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">
                    {{ __('parent-signup.privacy_read_prefix') }}
                    <button type="button" onclick="openLegalModal('privacyModal')" class="text-blue-600 hover:underline font-medium">{{ __('parent-signup.privacy_link') }}</button>
                </span>
            </label>

            <x-legal-document-modal :document="$privacyPolicy" modal-id="privacyModal" :locale="$locale ?? null" />
        @endif
    </div>
@endif
