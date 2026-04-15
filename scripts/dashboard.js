// dashboard.js – hardcoded suggestions with synonyms

document.addEventListener('DOMContentLoaded', () => {

    function toast(msg) {
        const el = document.getElementById('toast');
        if (!el) return;
        document.getElementById('toastMsg').textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2600);
    }

    // ========== SUGGESTIONS WITH SYNONYMS ==========
    const suggestionsMap = [
        { term: "Headsets", redirect: "Headsets" },
        { term: "Headset", redirect: "Headsets" },
        { term: "Earphones", redirect: "Headsets" },
        { term: "Headphones", redirect: "Headsets" },
        { term: "Smartphones", redirect: "Smartphones" },
        { term: "Smartphone", redirect: "Smartphones" },
        { term: "Phone", redirect: "Smartphones" },
        { term: "Cellphone", redirect: "Cell phones" },
        { term: "Cell phones", redirect: "Cell phones" },
        { term: "Mobile", redirect: "Smartphones" },
        { term: "Laptops", redirect: "Laptops" },
        { term: "Laptop", redirect: "Laptops" },
        { term: "Computer", redirect: "Computers & Laptops" },
        { term: "Cameras", redirect: "Cameras" },
        { term: "Camera", redirect: "Cameras" },
        { term: "Watches", redirect: "Watches" },
        { term: "Watch", redirect: "Watches" },
        { term: "TV", redirect: "TV sets" },
        { term: "Television", redirect: "TV sets" },
        { term: "Sound", redirect: "Sound" },
        { term: "Speaker", redirect: "Sound" },
        { term: "Kitchen", redirect: "Kitchen Equipment" }
    ];

    const searchInput = document.getElementById('searchInput');
    const resultsDiv = document.getElementById('searchResults');

    function showSuggestions(query) {
        if (query.length < 2) {
            resultsDiv.classList.remove('open');
            return;
        }
        const matches = suggestionsMap.filter(item => 
            item.term.toLowerCase().includes(query.toLowerCase())
        );
        if (matches.length === 0) {
            resultsDiv.classList.remove('open');
            return;
        }
        resultsDiv.innerHTML = matches.map(item => 
            `<div class="search-result-item" data-redirect="${item.redirect}">${escapeHtml(item.term)}</div>`
        ).join('');
        resultsDiv.classList.add('open');

        document.querySelectorAll('.search-result-item').forEach(el => {
            el.addEventListener('click', () => {
                const redirectCat = el.getAttribute('data-redirect');
                searchInput.value = el.textContent;
                resultsDiv.classList.remove('open');
                window.location.href = `../user/category.php?cat=${encodeURIComponent(redirectCat)}`;
            });
        });
    }

    function escapeHtml(str) {
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();
        debounceTimer = setTimeout(() => showSuggestions(query), 200);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-field-wrap')) resultsDiv.classList.remove('open');
    });

    // ========== SEARCH ON ENTER / BUTTON ==========
    const searchBtn = document.getElementById('searchBtn');
    function performSearch() {
        const query = searchInput.value.trim();
        if (query === '') {
            toast('Please enter a search term.');
            return;
        }
        window.location.href = `../user/category.php?search=${encodeURIComponent(query)}`;
    }
    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });

    // Toast for category links
    document.querySelectorAll('.category-links a, .view-all-btn, .item-subcategory').forEach(link => {
        link.addEventListener('click', (e) => {
            const text = link.querySelector('span')?.innerText || link.innerText;
            toast(`Going to ${text}…`);
        });
    });
});