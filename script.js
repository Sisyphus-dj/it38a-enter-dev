// Product Management Logic
let products = [];
const productForm = document.getElementById('productForm');
const productList = document.getElementById('product-list');
const searchBar = document.getElementById('searchBar');
const totalPriceDisplay = document.getElementById('totalPrice');

// Add or Update Product
if (productForm) {
    productForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const name = document.getElementById('productName').value.trim();
        const price = parseFloat(document.getElementById('productPrice').value.trim());

        if (name && price && !isNaN(price)) {
            const product = {
                id: Date.now(),
                name,
                price
            };
            products.push(product);
            renderProducts();
            productForm.reset();
        } else {
            alert('Please enter a valid name and price.');
        }
    });
}

// Render Product List
function renderProducts() {
    if (productList) {
        productList.innerHTML = '';
        products.forEach(product => {
            const productItem = document.createElement('div');
            productItem.classList.add('product-item');
            productItem.innerHTML = `
                <p><strong>${product.name}</strong> - $${product.price.toFixed(2)}</p>
                <button onclick="deleteProduct(${product.id})">Delete</button>
            `;
            productList.appendChild(productItem);
        });
        calculateTotalPrice();
    }
}

// Delete Product
function deleteProduct(id) {
    products = products.filter(p => p.id !== id);
    renderProducts();
}

// Calculate Total Price
function calculateTotalPrice() {
    if (totalPriceDisplay) {
        const totalPrice = products.reduce((acc, product) => acc + product.price, 0);
        totalPriceDisplay.textContent = `Total Price: $${totalPrice.toFixed(2)}`;
    }
}
