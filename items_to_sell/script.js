const cartIcon = document.querySelector("#cart-icon");
const cart = document.querySelector(".cart");
const cartClose = document.querySelector("#cart-close");

cartIcon.addEventListener("click", () => cart.classList.add("active"));
cartClose.addEventListener("click", () => cart.classList.remove("active"));

const addCartButtons = document.querySelectorAll(".add-cart");
const cartContent = document.querySelector(".cart-content");
let cartItemCount = 0;

// Update total price
const updateTotalPrice = () => {
    const totalPriceElement = document.querySelector(".total-price");
    const cartBoxes = cartContent.querySelectorAll(".cart-box");
    let total = 0;

    cartBoxes.forEach(cartBox => {
        let price = parseFloat(cartBox.dataset.price);  // already clean number
        let qty = parseInt(cartBox.querySelector(".number").textContent);
        total += price * qty;
    });

    totalPriceElement.textContent = "₱" + total.toFixed(2); // FIXED (no NaN)
};

// Add to cart
addCartButtons.forEach(button => {
    button.addEventListener("click", (event) => {
        const productBox = event.target.closest(".product-box");
        addToCart(productBox);
    });
});

const addToCart = (productBox) => {
    const productImgSrc = productBox.querySelector("img").src;
    const productTitle = productBox.querySelector(".product-title").textContent;
    const productPriceText = productBox.querySelector(".price").textContent;
    const productPrice = parseFloat(productPriceText.replace("₱", "").replace(",", ""));

    // Prevent duplicates
    const cartItems = cartContent.querySelectorAll(".cart-product-title");
    for (let item of cartItems) {
        if (item.textContent === productTitle) {
            alert("This is already in the cart.");
            return;
        }
    }

    const cartBox = document.createElement("div");
    cartBox.classList.add("cart-box");
    cartBox.dataset.price = productPrice;

    cartBox.innerHTML = `
        <img src="${productImgSrc}" class="cart-img">
        <div class="cart-detail">
            <h2 class="cart-product-title">${productTitle}</h2>
            <span class="cart-price">₱${productPrice.toFixed(2)}</span>
            <div class="cart-quantity-box">
                <div class="cart-quantity">
                    <button class="decrement">-</button>
                    <span class="number">1</span>
                    <button class="increment">+</button>
                </div>
            </div>
            <i class="ri-delete-bin-line cart-remove"></i>
        </div>
    `;

    cartContent.appendChild(cartBox);

    // Remove item
    cartBox.querySelector(".cart-remove").addEventListener("click", () => {
        cartBox.remove();
        updateCartCount(-1);
        updateTotalPrice();
    });

    // Quantity controls
    const decrementBtn = cartBox.querySelector(".decrement");
    const incrementBtn = cartBox.querySelector(".increment");
    const numberElement = cartBox.querySelector(".number");

    decrementBtn.addEventListener("click", () => {
        let qty = parseInt(numberElement.textContent);
        if (qty > 1) {
            qty--;
            numberElement.textContent = qty;
            updateTotalPrice();
        }
    });

    incrementBtn.addEventListener("click", () => {
        let qty = parseInt(numberElement.textContent);
        qty++;
        numberElement.textContent = qty;
        updateTotalPrice();
    });

    updateCartCount(1);
    updateTotalPrice();
};

// Update cart badge
const updateCartCount = (change) => {
    const badge = document.querySelector(".cart-item-count");
    cartItemCount += change;

    if (cartItemCount > 0) {
        badge.style.visibility = "visible";
        badge.textContent = cartItemCount;
    } else {
        badge.style.visibility = "hidden";
        badge.textContent = "";
    }
};

// Buy now button
document.querySelector(".btn-buy").addEventListener("click", () => {
    const cartBoxes = cartContent.querySelectorAll(".cart-box");

    if (cartBoxes.length === 0) {
        alert("Your cart is empty. Please add items to your cart before buying.");
        return;
    }

    cartBoxes.forEach(box => box.remove());
    cartItemCount = 0;
    updateCartCount(0);
    updateTotalPrice();

    alert("Thank you for your purchase!");
});
