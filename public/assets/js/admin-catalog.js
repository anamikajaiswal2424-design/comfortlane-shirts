document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-copy-feed]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copyFeed || '';
            if (!value) return;
            try {
                await navigator.clipboard.writeText(value);
                button.textContent = 'Copied';
            } catch (error) {
                const input = button.parentElement?.querySelector('input');
                if (input) {
                    input.focus();
                    input.select();
                }
                button.textContent = 'Select URL';
            }
            window.setTimeout(() => { button.textContent = 'Copy'; }, 1800);
        });
    });
});
