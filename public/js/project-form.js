document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('project-budget');
    if (!container) return;
    let next = Array.from(container.querySelectorAll('[name]')).reduce((max, input) => {
        const index = input.name.match(/^items\[(\d+)\]/);
        return index ? Math.max(max, Number(index[1]) + 1) : max;
    }, 0);
    document.getElementById('add-project-budget').addEventListener('click', () => {
        if (container.querySelectorAll('.budget-line').length >= 50) return;
        const template = document.getElementById('project-budget-template');
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[name]').forEach(input => { input.name = input.name.replaceAll('__INDEX__', String(next)); });
        next++;
        container.appendChild(fragment);
    });
    container.addEventListener('click', event => {
        const button = event.target.closest('[data-remove-budget]');
        if (button) button.closest('.budget-line').remove();
    });
});
