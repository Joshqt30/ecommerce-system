// dashboard.js

document.addEventListener('DOMContentLoaded', () => {

    // Toast helper
    function toast(msg) {
        const el = document.getElementById('toast');
        if (!el) return;
        document.getElementById('toastMsg').textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2600);
    }

    // Nav links: show toast, wait a moment, then navigate
    document.querySelectorAll('.nav-links a:not(.cart-btn)').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            if (a._navTimeout) clearTimeout(a._navTimeout);
            toast(`Navigating to ${a.textContent.trim()}…`);
            a._navTimeout = setTimeout(() => {
                window.location.href = a.href;
            }, 500);
        });
    });

    // Category sub-links
    document.querySelectorAll('.category-links a').forEach(a => {
        a.addEventListener('click', e => {
            e.preventDefault();
            toast(`Browsing: ${a.dataset.category || a.textContent.trim()}`);
        });
    });

    // View all buttons
    document.querySelectorAll('.view-all-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            toast(`Viewing all in: ${btn.dataset.category}`);
        });
    });

    // Sub-category icons
    document.querySelectorAll('.item-subcategory').forEach(item => {
        item.addEventListener('click', () => {
            toast(`Browsing: ${item.dataset.category}`);
        });
    });

    // Search with autocomplete
    const suggestions = [
        'Samsung Galaxy S24', 'iPhone 15 Pro', 'MacBook Air M3',
        'Sony WH-1000XM5', 'LG OLED TV 55"', 'Apple Watch Series 9',
        'Canon EOS R50', 'KitchenAid Stand Mixer', 'Dell XPS 15',
        'Bose QuietComfort 45', 'iPad Pro 12.9"', 'Logitech MX Master 3',
        'Samsung 4K Monitor', 'Dyson V15 Vacuum', 'Nintendo Switch OLED'
    ];

    const input   = document.getElementById('searchInput');
    const results = document.getElementById('searchResults');
    const btn     = document.getElementById('searchBtn');

    function renderSuggestions(q) {
        if (!q.trim()) {
            results.classList.remove('open');
            return;
        }
        const matches = suggestions.filter(s => s.toLowerCase().includes(q.toLowerCase()));
        if (!matches.length) {
            results.classList.remove('open');
            return;
        }

        results.innerHTML = matches.map(m => `<div class="search-result-item">${m}</div>`).join('');
        results.classList.add('open');

        results.querySelectorAll('.search-result-item').forEach(row => {
            row.addEventListener('click', () => {
                input.value = row.textContent;
                results.classList.remove('open');
                toast(`Searching for "${row.textContent}"…`);
            });
        });
    }

    input.addEventListener('input', () => renderSuggestions(input.value));

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            results.classList.remove('open');
            if (input.value.trim()) toast(`Searching for "${input.value.trim()}"…`);
        }
        if (e.key === 'Escape') results.classList.remove('open');
    });

    btn.addEventListener('click', () => {
        results.classList.remove('open');
        if (input.value.trim()) toast(`Searching for "${input.value.trim()}"…`);
        else toast('Please enter a search term.');
    });

    document.addEventListener('click', e => {
        if (!e.target.closest('.search-field-wrap')) results.classList.remove('open');
    });


});