(function () {
    'use strict';

    const hub = document.querySelector('[data-training-hub]');
    if (!hub) return;

    const aiForm = hub.querySelector('[data-ai-generate]')?.closest('form');
    if (aiForm) {
        aiForm.addEventListener('submit', () => {
            const btn = aiForm.querySelector('[data-ai-generate]');
            const text = btn?.querySelector('.ws-training-ai-form__submit-text');
            const spinner = btn?.querySelector('.ws-training-ai-form__spinner');
            if (btn) btn.disabled = true;
            if (text) text.textContent = 'Generating…';
            if (spinner) spinner.hidden = false;
        });
    }

    const selectAll = hub.querySelector('[data-qa-select-all]');
    if (selectAll) {
        selectAll.addEventListener('click', () => {
            const checks = hub.querySelectorAll('.qa-approve-check');
            const allChecked = [...checks].every((c) => c.checked);
            checks.forEach((c) => { c.checked = !allChecked; });
            selectAll.textContent = allChecked ? 'Select all' : 'Deselect all';
        });
    }
})();
