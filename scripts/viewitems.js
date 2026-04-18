// product.js

document.addEventListener('DOMContentLoaded', () => {

    const priceEl = document.getElementById('priceDisplay');
    const BASE_PRICE = parseFloat(priceEl.dataset.price);    
    let qty = 0;
    let wished = false;

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

    // Color options
    document.querySelectorAll('#colorOpts .opt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#colorOpts .opt-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // Quantity
    function updatePrice() {
        const total = qty > 0 ? (BASE_PRICE * qty).toFixed(2) : BASE_PRICE.toFixed(2);
        document.getElementById('priceDisplay').textContent = '$' + total;
        document.getElementById('qtyVal').value = qty;
    }

    document.getElementById('qtyPlus').addEventListener('click', () => { 
        qty++; 
        updatePrice(); 
    });
    document.getElementById('qtyMinus').addEventListener('click', () => { 
        if (qty > 0) { qty--; updatePrice(); } 
    });

    // Toast function
    function showToast(msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2800);
    }

    // Add to Cart
    document.getElementById('addToCart').addEventListener('click', () => {
        const amount = qty > 0 ? qty : 1;
        if (qty === 0) { qty = 1; updatePrice(); }
        
        if (typeof CartPanel !== 'undefined') {
            CartPanel.addItem({
                id: 1,
                name: 'Microsoft Lumia 640 XL RM-1065 8GB Dual Sim',
                price: BASE_PRICE,
                image: "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=300&q=80"
            });
        }
        showToast(`${amount} item${amount > 1 ? 's' : ''} added to cart!`);
    });

    // Buy Now
    document.getElementById('buyNow').addEventListener('click', () => {
        const amount = qty > 0 ? qty : 1;
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

    // See More / See Less functionality
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
});