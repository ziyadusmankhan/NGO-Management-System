document.addEventListener('DOMContentLoaded', () => {
    const list = document.getElementById('purchase-items');
    const add = document.getElementById('add-purchase-item');
    if (!list || !add) return;
    let next = Math.max(...Array.from(list.querySelectorAll('[name]'), el => Number(el.name.match(/items\[(\d+)\]/)?.[1] || 0))) + 1;
    add.addEventListener('click', () => {
        if (list.children.length >= 50) return;
        const row = list.firstElementChild.cloneNode(true);
        row.querySelectorAll('[name]').forEach(el => { el.name = el.name.replace(/items\[\d+\]/, `items[${next}]`); el.value = ''; });
        list.append(row); next++;
    });
    list.addEventListener('click', e => {
        const button = e.target.closest('.remove-purchase-item');
        if (button && list.children.length > 1) button.closest('.item-row').remove();
    });
});
