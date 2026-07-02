@if(\App\Support\SignupLocale::shouldApplyStudentLocale())
    <div class="flex justify-end mb-4" dir="ltr">
        <x-signup-locale-switcher compact />
    </div>
@endif
