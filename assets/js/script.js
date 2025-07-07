document.addEventListener('DOMContentLoaded', function () {
  console.log("Terms Gate script loaded"); 
    var form = document.querySelector('.tg-terms-form');
    if (!form) return;

    var checkbox = form.querySelector('input[type="checkbox"][name="tg_agree"]');
    var button = form.querySelector('button[type="submit"]');
    if (checkbox && button) {
        button.disabled = !checkbox.checked;
        checkbox.addEventListener('change', function () {
            button.disabled = !checkbox.checked;
        });
    }
});