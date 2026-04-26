// ── Toast ─────────────────────────────────────────────
function toast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2600);
}

function addToCart(btn, productId) {
    fetch('../includes/add.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ product_id: productId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            toast('Added to cart');
            if (typeof CartPanel !== 'undefined') {
                CartPanel.open();
                CartPanel.render();
            }
        } else {
            toast(data.message || 'Error adding to cart');
        }
    })
    .catch(err => {
        console.error(err);
        toast('Something went wrong');
    });
}

function buyNow(id) {
    // Go to viewitems.php first, not directly to checkout
    window.location.href = '../user/viewitems.php?id=' + id;
}

// ── Smart search ──────────────────────────────────────
const searchInput   = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
let searchTimer;

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();

    if (q.length < 2) {
        searchResults.classList.remove('open');
        searchResults.innerHTML = '';
        return;
    }

    searchTimer = setTimeout(() => {
        // Use the global category variable exposed by PHP
        const cat = window.category || '';
        fetch(`../includes/search-suggestions.php?q=${encodeURIComponent(q)}&cat=${encodeURIComponent(cat)}`)
            .then(r => {
                if (!r.ok) throw new Error('Network error');
                return r.json();
            })
            .then(data => {
                if (!data || !data.length) {
                    searchResults.classList.remove('open');
                    return;
                }

                searchResults.innerHTML = data.map(item => `
                    <div class="search-result-item" data-name="${item.name.replace(/"/g,'&quot;')}">
                        <span class="suggest-name">${item.name}</span>
                        <span class="suggest-cat">${item.category}</span>
                    </div>
                `).join('');

                searchResults.classList.add('open');

                searchResults.querySelectorAll('.search-result-item').forEach(row => {
                    row.addEventListener('click', () => {
                        searchInput.value = row.dataset.name;
                        searchResults.classList.remove('open');
                        document.getElementById('searchForm').submit();
                    });
                });
            })
            .catch(err => {
                console.warn('Search suggestions failed:', err);
                searchResults.classList.remove('open');
            });
    }, 280);
});

searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { searchResults.classList.remove('open'); }
    if (e.key === 'Escape') { searchResults.classList.remove('open'); }
});

document.addEventListener('click', e => {
    if (!e.target.closest('.search-field-wrap')) {
        searchResults.classList.remove('open');
    }
});

// ── Filter form: radio auto-submit ───────────────────
document.querySelectorAll('#filterForm input[type="radio"]').forEach(r => {
    r.addEventListener('change', () => document.getElementById('filterForm').submit());
});

// ── Price slider: submit on release ──────────────────
const priceSlider = document.getElementById('priceRange');
if (priceSlider) {
    priceSlider.addEventListener('change', () => {
        document.getElementById('filterForm').submit();
    });
}