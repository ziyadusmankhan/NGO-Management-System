/* Apply the saved appearance before styles paint. Storage can be unavailable. */
(() => {
 let theme = 'light';
 try { const saved = localStorage.getItem('foundation-theme'); if (saved === 'dark' || saved === 'light') theme = saved; } catch (_) {}
 document.documentElement.dataset.theme = theme;
})();
