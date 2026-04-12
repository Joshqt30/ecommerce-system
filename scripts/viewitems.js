// product.js

document.addEventListener('DOMContentLoaded', () => {

    const BASE_PRICE = 298;
    let qty = 0;
    let wished = false;

    const thumbImages = [
        "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=700&q=85",
        "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=700&q=85",
        "https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=700&q=85",
        "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=700&q=85",
        "https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=700&q=85",
        "https://images.unsplash.com/photo-1484788984921-03950022c9ef?w=700&q=85"
    ];

    const mainImg = document.getElementById('mainImg');

    // Thumbnail switching
    document.querySelectorAll('.thumb').forEach(t => {
        t.addEventListener('click', () => {
            document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
            t.classList.add('active');
            mainImg.classList.add('switching');
            setTimeout(() => {
                mainImg.src = thumbImages[+t.dataset.idx];
                mainImg.classList.remove('switching');
            }, 200);
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

    // Wishlist
    document.getElementById('wishBtn').addEventListener('click', () => {
        wished = !wished;
        const btn = document.getElementById('wishBtn');
        btn.classList.toggle('wished', wished);
        btn.innerHTML = wished 
            ? `<svg width="17" height="17" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
            : `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
        showToast(wished ? 'Added to wishlist ♥' : 'Removed from wishlist');
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

});