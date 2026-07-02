@php
    $gameStrings = \App\Support\SignupLocale::gameStrings();
@endphp
<script>
window.gameI18n = @json($gameStrings);
window.gameT = function (key, replacements) {
    let text = window.gameI18n[key] ?? key;
    if (!replacements) {
        return text;
    }
    Object.entries(replacements).forEach(function (entry) {
        text = text.replaceAll(':' + entry[0], String(entry[1]));
    });
    return text;
};
</script>
