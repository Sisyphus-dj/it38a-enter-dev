// ===== Smooth Scrolling Navigation =====
document.querySelectorAll('nav ul li a').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();

        const targetId = this.getAttribute('href');
        const targetElement = document.querySelector(targetId);

        if (targetElement) {
            const navbarHeight = document.querySelector('nav').offsetHeight;
            const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
            
            window.scrollTo({
                top: targetPosition - navbarHeight + 1, // Adjusted to prevent cutoff
                behavior: 'smooth'
            });

            // Active Link Highlight
            document.querySelectorAll('nav ul li a').forEach(link => link.classList.remove('active'));
            this.classList.add('active');
        }
    });
});

// ===== Active Link Highlight on Scroll =====
window.addEventListener('scroll', () => {
    const sections = document.querySelectorAll('section');
    const navbarHeight = document.querySelector('nav').offsetHeight;

    sections.forEach(section => {
        const sectionTop = section.offsetTop - navbarHeight;
        const sectionHeight = section.offsetHeight;

        if (window.pageYOffset >= sectionTop && window.pageYOffset < sectionTop + sectionHeight) {
            const activeLink = document.querySelector(`nav ul li a[href="#${section.id}"]`);
            document.querySelectorAll('nav ul li a').forEach(link => link.classList.remove('active'));
            if (activeLink) activeLink.classList.add('active');
        }
    });
});

// ===== Contact Form Logic =====
document.getElementById('contactForm').addEventListener('submit', function (event) {
    event.preventDefault();
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const message = document.getElementById('message').value.trim();

    if (!name || !email || !message) {
        alert('Please fill in all fields.');
        return;
    }

    if (!/^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/.test(email)) {
        alert('Please enter a valid email address.');
        return;
    }

    alert('Message Sent Successfully!');
    document.getElementById('contactForm').reset();
});

// ===== Product Management Logic =====
let products = [];
const productForm = document.getElementById('productForm');
const productList = document.getElementById('product-list');
const searchBar = document.getElementById('searchBar');
const totalPriceDisplay = document.getElementById('totalPrice');

// Add or Update Product
productForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const name = document.getElementById('productName').value.trim();
    const price = parseFloat(document.getElementById('productPrice').value.trim());
    const productId = productForm.dataset.editId;

    if (name && price && !isNaN(price)) {
        if (productId) {
            // Update Existing Product
            const product = products.find(p => p.id == productId);
            product.name = name;
            product.price = price;
            delete productForm.dataset.editId;
        } else {
            // Add New Product
            const product = {
                id: Date.now(),
                name,
                price
            };
            products.push(product);
        }
        renderProducts();
        productForm.reset();
    } else {
        alert('Please enter a valid name and price.');
    }
});

// ===== Render Product List =====
function renderProducts() {
    productList.innerHTML = '';
    const filteredProducts = products.filter(product =>
        product.name.toLowerCase().includes(searchBar.value.toLowerCase())
    );

    filteredProducts.forEach(product => {
        const productItem = document.createElement('div');
        productItem.classList.add('product-item');
        productItem.innerHTML = `
            <p><strong>${product.name}</strong> - $${product.price.toFixed(2)}</p>
            <button onclick="editProduct(${product.id})">Edit</button>
            <button onclick="deleteProduct(${product.id})">Delete</button>
        `;
        productList.appendChild(productItem);
    });

    calculateTotalPrice();
}

// ===== Edit Product =====
function editProduct(id) {
    const product = products.find(p => p.id === id);
    if (product) {
        document.getElementById('productName').value = product.name;
        document.getElementById('productPrice').value = product.price;
        productForm.dataset.editId = id;
    }
}

// ===== Delete Product =====
function deleteProduct(id) {
    products = products.filter(p => p.id !== id);
    renderProducts();
}

// ===== Search Filter =====
searchBar.addEventListener('input', renderProducts);

// ===== Calculate Total Price =====
function calculateTotalPrice() {
    const totalPrice = products.reduce((acc, product) => acc + product.price, 0);
    totalPriceDisplay.textContent = `Total Price: $${totalPrice.toFixed(2)}`;
}