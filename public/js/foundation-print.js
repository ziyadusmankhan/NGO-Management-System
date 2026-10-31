/* Build a readable A4 print layout without changing on-screen data or signed evidence. */
(() => {
    const prepare = () => {
        document.querySelectorAll('table:not(.a4-wide-source)').forEach(table => {
            const headings = Array.from(table.querySelectorAll('thead tr:first-child th'));
            if (headings.length <= 5) return;
            const records = document.createElement('section');
            records.className = 'a4-print-records';
            table.querySelectorAll('tbody tr').forEach(row => {
                const cells = Array.from(row.children);
                const card = document.createElement('article');
                card.className = 'a4-print-record';
                cells.forEach((cell, index) => {
                    const field = document.createElement('div');
                    const label = document.createElement('strong');
                    label.textContent = cells.length === 1 ? '' : (headings[index]?.textContent.trim() ?? 'Details');
                    const value = document.createElement('div');
                    value.textContent = cell.innerText.trim();
                    field.append(label, value); card.append(field);
                });
                records.append(card);
            });
            table.classList.add('a4-wide-source');
            table.after(records);
        });
    };
    addEventListener('beforeprint', prepare);
    addEventListener('afterprint', () => {
        document.querySelectorAll('.a4-print-records').forEach(element => element.remove());
        document.querySelectorAll('.a4-wide-source').forEach(table => table.classList.remove('a4-wide-source'));
    });
})();
