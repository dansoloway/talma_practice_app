@push('scripts')
<script>
function togglePasswordField(button) {
    var wrapper = button.closest('.relative');
    if (!wrapper) return;
    var input = wrapper.querySelector('input[type="password"], input[type="text"]');
    var icon = button.querySelector('i');
    if (!input || !icon) return;

    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    icon.classList.toggle('fa-eye', showing);
    icon.classList.toggle('fa-eye-slash', !showing);
    button.setAttribute('aria-label', showing
        ? (button.dataset.showLabel || 'Show password')
        : (button.dataset.hideLabel || 'Hide password'));
}

(function(){
    var section = document.getElementById('children-section');
    if(!section) return;
    function setRowDisabled(row, disabled) {
        row.querySelectorAll('input, select').forEach(function(el) { el.disabled = disabled; });
    }
    function updateStudentContactFields(block, isEmail) {
        var emailWrap = block.querySelector('.student-contact-email');
        var phoneWrap = block.querySelector('.student-contact-phone');
        if (emailWrap) emailWrap.style.display = isEmail ? '' : 'none';
        if (phoneWrap) phoneWrap.style.display = isEmail ? 'none' : '';
        var emailInput = block.querySelector('.student-email-input');
        var prefixSelect = block.querySelector('.student-phone-prefix');
        var restInput = block.querySelector('.student-phone-rest');
        if (emailInput) { emailInput.disabled = !isEmail; emailInput.required = isEmail; }
        if (prefixSelect) { prefixSelect.disabled = isEmail; prefixSelect.required = !isEmail; }
        if (restInput) { restInput.disabled = isEmail; restInput.required = !isEmail; }
    }
    function syncLoginRow(row) {
        if (row.classList.contains('hidden')) return;
        var block = row.querySelector('.student-separate-fields');
        if (!block) return;
        var sep = row.querySelector('input[value="separate"][name*="[login_type]"]');
        var isSeparate = sep && sep.checked;
        block.style.display = isSeparate ? '' : 'none';
        if (!isSeparate) {
            block.querySelectorAll('input, select').forEach(function(el) {
                el.required = false;
                el.disabled = true;
            });
        } else {
            block.querySelectorAll('input, select').forEach(function(el) { el.disabled = false; });
            var typePhone = block.querySelector('input.student-contact-type[value="phone"]');
            updateStudentContactFields(block, !(typePhone && typePhone.checked));
        }
    }
    section.querySelectorAll('.child-row').forEach(function(row) {
        if (row.classList.contains('hidden')) setRowDisabled(row, true);
        else { setRowDisabled(row, false); syncLoginRow(row); }
    });
    var addBtn = section.querySelector('#add-child-btn');
    if (addBtn) addBtn.onclick = function(){
        section.querySelectorAll('.child-row').forEach(function(row){
            if(row.classList.contains('hidden')) {
                row.classList.remove('hidden');
                setRowDisabled(row, false);
                syncLoginRow(row);
                return false;
            }
        });
    };
    section.querySelectorAll('.remove-child').forEach(function(btn){
        btn.onclick = function(){
            var visible = section.querySelectorAll('.child-row:not(.hidden)');
            if(visible.length <= 1) return;
            var last = visible[visible.length - 1];
            last.classList.add('hidden');
            setRowDisabled(last, true);
        };
    });
    section.querySelectorAll('input[name*="[login_type]"]').forEach(function(radio){
        radio.onchange = function(){ syncLoginRow(this.closest('.child-row')); };
    });
    section.querySelectorAll('.student-contact-type').forEach(function(radio){
        radio.onchange = function(){
            var row = this.closest('.child-row');
            var sep = row.querySelector('input[value="separate"][name*="[login_type]"]');
            if (!sep || !sep.checked) return;
            updateStudentContactFields(this.closest('.student-separate-fields'), this.value === 'email');
        };
    });
})();
</script>
@endpush
