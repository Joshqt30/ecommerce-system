// viewitems.js - updated with variant support

document.addEventListener('DOMContentLoaded', () => {

    const priceEl = document.getElementById('priceDisplay');
    const BASE_PRICE = parseFloat(priceEl.dataset.price);
    let qty = 1;
    let selectedVariantId = null;
    let currentVariantPrice = BASE_PRICE;

    // Variants data from PHP (embedded via script tag - see below)
    const variants = window.productVariants || [];

    // Thumbnail gallery switching
    const mainImg = document.getElementById('mainImg');
    document.querySelectorAll('.thumb').forEach(t => {
        t.addEventListener('click', () => {
            document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
            t.classList.add('active');
            mainImg.classList.add('switching');
            setTimeout(() => {
                mainImg.src = t.querySelector('img').src;
                mainImg.classList.remove('switching');
            }, 150);
        });
    });

    // Generic variant option click handler (replaces old #colorOpts)
    function handleVariantClick(e) {
        const btn = e.target.closest('.opt-btn');
        if (!btn) return;

        const parent = btn.closest('.option-buttons');
        // Remove active class from siblings
        parent.querySelectorAll('.opt-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // After selection changes, find matching variant
        updateSelectedVariant();
    }

    // Attach listener to all option buttons (use event delegation)
    document.querySelector('.product-info').addEventListener('click', (e) => {
        if (e.target.closest('.opt-btn')) {
            handleVariantClick(e);
        }
    });

    // Find the variant that matches all selected attributes
    function updateSelectedVariant() {
        const selectedAttrs = {};
        document.querySelectorAll('.option-buttons').forEach(group => {
            const activeBtn = group.querySelector('.opt-btn.active');
            if (activeBtn) {
                const attrName = group.dataset.attribute;
                selectedAttrs[attrName] = activeBtn.dataset.val;
            }
        });

                // Find matching variant
                const matched = variants.find(v => {
                    const attrs = JSON.parse(v.attributes);
                    return Object.keys(selectedAttrs).every(key => attrs[key] === selectedAttrs[key]);
                });

            if (matched) {
            selectedVariantId = matched.id;
            currentVariantPrice = matched.price !== null ? parseFloat(matched.price) : BASE_PRICE;
            updatePriceDisplay();

          if (matched.image_url) {
                mainImg.src = (window.productImgsBase || '') + matched.image_url;
            }

          // ---------- STOCK STATUS UPDATE (with quantity) ----------
        const stockStatusEl = document.getElementById('stockStatus');
        const stockQtyEl = document.getElementById('stockQuantity');

        if (matched) {
            const inStock = matched.stock_quantity > 0;

            if (stockStatusEl) {
                stockStatusEl.textContent = inStock ? 'In Stock' : 'Out of Stock';
                stockStatusEl.style.color = inStock ? '#16a34a' : '#dc2626';
            }

            if (stockQtyEl) {
                stockQtyEl.textContent = inStock ? `(${matched.stock_quantity} available)` : '';
            }

            // Disable add to cart if out of stock
            const addBtn = document.getElementById('addToCart');
            if (addBtn) {
                if (!inStock) {
                    addBtn.disabled = true;
                    addBtn.style.opacity = '0.5';
                } else {
                    addBtn.disabled = false;
                    addBtn.style.opacity = '1';
                }
            }
        } else {
            // No matching variant — reset
            if (stockStatusEl) {
                stockStatusEl.textContent = 'Select options';
                stockStatusEl.style.color = '#6b7280';
            }
            if (stockQtyEl) {
                stockQtyEl.textContent = '';
            }

            const addBtn = document.getElementById('addToCart');
            if (addBtn) {
                addBtn.disabled = true;
                addBtn.style.opacity = '0.5';
            }
        }
      }   

    }

    function updatePriceDisplay() {
        const total = currentVariantPrice * qty;
        priceEl.textContent = '₱' + total.toFixed(2);
    }

    // Quantity controls
    document.getElementById('qtyPlus').addEventListener('click', () => {
        qty++;
        document.getElementById('qtyVal').value = qty;
        updatePriceDisplay();
    });
    document.getElementById('qtyMinus').addEventListener('click', () => {
        if (qty > 1) {
            qty--;
            document.getElementById('qtyVal').value = qty;
            updatePriceDisplay();
        }
    });

    // Toast
    function showToast(msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2800);
    }

    // Add to Cart
    document.getElementById('addToCart').addEventListener('click', () => {
        if (!selectedVariantId && variants.length > 0) {
            showToast('Please select a variant');
            return;
        }

        const variantId = selectedVariantId || (variants[0]?.id ?? null);
        const amount = qty;

        if (typeof CartPanel !== 'undefined') {
            CartPanel.addItem({
            variantId: variantId,
            productId: window.productId,
            name: document.querySelector('.product-title').textContent,
            price: currentVariantPrice,
            quantity: amount,
            image: mainImg.src
        });
        }
        showToast(`${amount} item${amount > 1 ? 's' : ''} added to cart!`);
    });

    // Buy Now
    document.getElementById('buyNow').addEventListener('click', () => {
        const amount = qty;
        showToast(`Proceeding to checkout for ${amount} item${amount > 1 ? 's' : ''}…`);
    });

    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });

    // See More / See Less
    const seeMoreBtn = document.getElementById('seeMoreBtn');
    if (seeMoreBtn) {
        const moreReviews = document.getElementById('moreReviews');
        const btnText = seeMoreBtn.querySelector('span');
        seeMoreBtn.addEventListener('click', () => {
            const isExpanded = moreReviews.classList.toggle('show');
            btnText.textContent = isExpanded ? 'See less' : 'See more';
            seeMoreBtn.classList.toggle('rotated', isExpanded);
        });
    }

    // Initialize: select first option in each group to have a default variant
    document.querySelectorAll('.option-buttons').forEach(group => {
        const firstBtn = group.querySelector('.opt-btn');
        if (firstBtn && !group.querySelector('.opt-btn.active')) {
            firstBtn.classList.add('active');
        }
    });
    updateSelectedVariant();
});