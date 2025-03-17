// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Product search functionality
  const searchInput = document.getElementById("product-search");
  if (searchInput) {
    searchInput.addEventListener("keyup", function () {
      const searchTerm = this.value.toLowerCase();
      const productCards = document.querySelectorAll(".product-card");

      productCards.forEach((card) => {
        const title = card
          .querySelector(".product-title")
          .textContent.toLowerCase();
        const description = card
          .querySelector(".product-description")
          .textContent.toLowerCase();

        if (title.includes(searchTerm) || description.includes(searchTerm)) {
          card.style.display = "block";
        } else {
          card.style.display = "none";
        }
      });
    });
  }

  // Quantity increment/decrement buttons
  const quantityBtns = document.querySelectorAll(".quantity-btn");
  if (quantityBtns) {
    quantityBtns.forEach((btn) => {
      btn.addEventListener("click", function () {
        const input = this.parentElement.querySelector("input");
        const currentValue = parseInt(input.value);

        if (this.classList.contains("increment") && currentValue < 99) {
          input.value = currentValue + 1;
        } else if (this.classList.contains("decrement") && currentValue > 1) {
          input.value = currentValue - 1;
        }

        // If we're on the cart page, update the cart
        if (document.querySelector(".cart-container")) {
          updateCartItem(input.dataset.itemId, input.value);
        }
      });
    });
  }

  // Form validation
  const forms = document.querySelectorAll("form");
  if (forms) {
    forms.forEach((form) => {
      form.addEventListener("submit", function (e) {
        const requiredFields = form.querySelectorAll("[required]");
        let isValid = true;

        requiredFields.forEach((field) => {
          if (!field.value.trim()) {
            isValid = false;
            field.classList.add("error");

            // Create error message if it doesn't exist
            let errorMsg = field.parentElement.querySelector(".error-message");
            if (!errorMsg) {
              errorMsg = document.createElement("div");
              errorMsg.className = "error-message";
              errorMsg.textContent = "This field is required";
              field.parentElement.appendChild(errorMsg);
            }
          } else {
            field.classList.remove("error");
            const errorMsg =
              field.parentElement.querySelector(".error-message");
            if (errorMsg) {
              errorMsg.remove();
            }
          }
        });

        if (!isValid) {
          e.preventDefault();
        }
      });
    });
  }
});

// Function to update cart item quantity using AJAX
function updateCartItem(itemId, quantity) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "update-cart.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (response.success) {
          // Update subtotal and total
          document.getElementById("item-subtotal-" + itemId).textContent =
            response.itemSubtotal;
          document.getElementById("cart-total").textContent =
            response.cartTotal;
        } else {
          alert("Error: " + response.message);
        }
      } catch (e) {
        console.error("Error parsing JSON response:", e);
      }
    }
  };

  xhr.send(
    "item_id=" +
      encodeURIComponent(itemId) +
      "&quantity=" +
      encodeURIComponent(quantity)
  );
}

// Function to add product to cart using AJAX
function addToCart(productId, quantity) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "add-to-cart.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (response.success) {
          alert("Product added to cart successfully!");
          // Update cart count in header if it exists
          const cartCount = document.querySelector(".cart-count");
          if (cartCount) {
            cartCount.textContent = response.cartCount;
          }
        } else {
          alert("Error: " + response.message);
        }
      } catch (e) {
        console.error("Error parsing JSON response:", e);
      }
    }
  };

  xhr.send(
    "product_id=" +
      encodeURIComponent(productId) +
      "&quantity=" +
      encodeURIComponent(quantity)
  );
}

// Function to load delivery services based on location
function loadDeliveryServices(location) {
  const xhr = new XMLHttpRequest();
  xhr.open(
    "GET",
    "get-delivery-services.php?location=" + encodeURIComponent(location),
    true
  );

  xhr.onload = function () {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        const servicesContainer = document.getElementById("delivery-services");

        servicesContainer.innerHTML = "";

        if (response.services && response.services.length > 0) {
          response.services.forEach((service) => {
            const serviceDiv = document.createElement("div");
            serviceDiv.className = "delivery-service-option";
            serviceDiv.innerHTML = `
                            <input type="radio" name="service_id" id="service-${service.service_id}" value="${service.service_id}" required>
                            <label for="service-${service.service_id}">
                                <strong>${service.service_name}</strong> - $${service.price}
                                <p>${service.description}</p>
                                <p>Estimated delivery time: ${service.estimated_time}</p>
                            </label>
                        `;
            servicesContainer.appendChild(serviceDiv);
          });
        } else {
          servicesContainer.innerHTML =
            "<p>No delivery services available for this location.</p>";
        }
      } catch (e) {
        console.error("Error parsing JSON response:", e);
      }
    }
  };

  xhr.send();
}

// Function to update order status (admin)
function updateOrderStatus(orderId, status) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "update-order-status.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onload = function () {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (response.success) {
          document.getElementById("status-" + orderId).textContent =
            response.statusText;
          alert("Order status updated successfully!");
        } else {
          alert("Error: " + response.message);
        }
      } catch (e) {
        console.error("Error parsing JSON response:", e);
      }
    }
  };

  xhr.send(
    "order_id=" +
      encodeURIComponent(orderId) +
      "&status=" +
      encodeURIComponent(status)
  );
}
