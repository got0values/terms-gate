(function() {
    var checkbox = document.querySelector('input[name="termga_enabled"]');
    var select = document.querySelector('select[name="termga_form_id"]');
    if (!checkbox || !select) return;
    function toggleSelect() {
        select.disabled = !checkbox.checked;
    }
    checkbox.addEventListener('change', toggleSelect);
    toggleSelect();
})();