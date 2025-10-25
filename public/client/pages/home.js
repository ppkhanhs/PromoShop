import { App, formatCurrency } from "../assets/app.js";

      const state = {
        promotions: [],
        promoProductsByPromo: new Map(),
        promoProductsByProduct: new Map(),
        products: [],
        filtered: [],
      };

      const productGrid = document.getElementById("product-grid");
      const promoList = document.getElementById("promo-list");

      function renderPromotions() {
        if (!state.promotions.length) {
          promoList.innerHTML =
            '<div class="muted">Chưa có chương trình nào trong ngày. Hãy quay lại sau nhé!</div>';
          document.getElementById("insight-promos").textContent = "0";
          document.getElementById("insight-savings").textContent = "0 đ";
          return;
        }

        document.getElementById("insight-promos").textContent = state.promotions.length;

        const maxDiscount = state.promotions.reduce((acc, promo) => {
          const items = state.promoProductsByPromo.get(promo.promo_id) || [];
          const top = items.reduce((best, item) => {
            const percent = Number(item.discount_percent || 0);
            const amount = Number(item.discount_amount || 0);
            const valuePercent = percent ? (percent / 100) * 50000 : 0;
            const value = Math.max(valuePercent, amount);
            return Math.max(best, value);
          }, 0);
          return Math.max(acc, top);
        }, 0);

        document.getElementById("insight-savings").textContent = formatCurrency(maxDiscount);

        promoList.innerHTML = state.promotions
          .map((promo) => {
            const items = state.promoProductsByPromo.get(promo.promo_id) || [];
            const timeRange = `${promo.start_date || "?"} → ${promo.end_date || "?"}`;
            return `
              <div class="mini-item">
                <div>
                  <strong>${promo.name}</strong>
                  <div class="muted" style="font-size:13px">Thời gian: ${timeRange}</div>
                </div>
                <span class="badge">Áp dụng ${items.length} sản phẩm</span>
              </div>
            `;
          })
          .join("");
      }

      function calculateDiscount(base, percent, amount) {
        let price = base;
        if (percent) price = Math.round((price * (100 - Number(percent))) / 100);
        if (amount) price = Math.max(0, price - Number(amount));
        return price;
      }

      function renderProducts(items = []) {
        productGrid.innerHTML = "";
        if (!items.length) {
          productGrid.innerHTML =
            '<div class="empty-state" style="grid-column: 1 / -1">Không tìm thấy sản phẩm phù hợp.</div>';
          document.getElementById("insight-products").textContent = "0";
          return;
        }

        document.getElementById("insight-products").textContent = items.length;

        items.forEach((item) => {
          const promos = state.promoProductsByProduct.get(item.product_id) || [];
          const bestPromo = promos[0];
          const basePrice = Number(item.price || 0);
          const priceAfter = bestPromo
            ? calculateDiscount(basePrice, bestPromo.discount_percent, bestPromo.discount_amount)
            : basePrice;

          const card = document.createElement("article");
          card.className = "product-card";
          card.innerHTML = `
            <div class="product-thumb">
              <img src="${
                item.image_url || `https://images.promoshop.vn/placeholder/${item.product_id}.jpg`
              }" alt="${item.name}" />
            </div>
            <div class="product-body">
              <div>
                <div class="product-name">${item.name}</div>
                <div class="muted" style="font-size:13px">${item.category || "Đồ uống"}</div>
              </div>
              <div class="price-line">
                <span>${formatCurrency(priceAfter)}</span>
                ${
                  priceAfter !== basePrice
                    ? `<span class="old">${formatCurrency(basePrice)}</span>`
                    : ""
                }
              </div>
              ${
                bestPromo
                  ? `<span class="badge success">
                      Tiết kiệm ${
                        bestPromo.discount_percent
                          ? bestPromo.discount_percent + "%"
                          : formatCurrency(bestPromo.discount_amount)
                      }
                    </span>`
                  : `<span class="muted" style="font-size:13px">Giá đã bao gồm thuế</span>`
              }
              <div class="card-actions">
                <button class="btn-outline" data-action="view" data-id="${item.product_id}">
                  Xem chi tiết
                </button>
                <button class="btn-primary" data-action="add" data-id="${item.product_id}">
                  Thêm vào giỏ
                </button>
              </div>
            </div>
          `;
          productGrid.appendChild(card);
        });
      }

      async function fetchPromotions() {
        try {
          const promoRes = await App.apiFetch("/api/customer/promotions/active", {
            auth: "public",
          });
          state.promotions = promoRes.data || [];

          const byPromo = new Map();
          const byProduct = new Map();

          await Promise.all(
            state.promotions.map(async (promo) => {
              const res = await App.apiFetch(
                `/api/customer/promotions/${promo.promo_id}/products`,
                { auth: "public" }
              );
              const entries = res.data || [];
              byPromo.set(promo.promo_id, entries);
              entries.forEach((entry) => {
                const productList = byProduct.get(entry.product_id) || [];
                productList.push(entry);
                byProduct.set(entry.product_id, productList);
              });
            })
          );

          byProduct.forEach((list, key) => {
            list.sort((a, b) => {
              const valueA =
                Number(a.discount_percent || 0) * 1000 + Number(a.discount_amount || 0);
              const valueB =
                Number(b.discount_percent || 0) * 1000 + Number(b.discount_amount || 0);
              return valueB - valueA;
            });
          });

          state.promoProductsByPromo = byPromo;
          state.promoProductsByProduct = byProduct;
          renderPromotions();
          renderProducts(state.filtered.length ? state.filtered : state.products);
        } catch (err) {
          promoList.innerHTML = `<div class="muted">Không thể tải khuyến mãi: ${
            err.message || "lỗi không xác định"
          }</div>`;
        }
      }

      async function fetchProducts() {
        try {
          const res = await App.apiFetch("/api/customer/products", { auth: "public" });
          state.products = res.data || [];
          state.filtered = [...state.products];
          renderProducts(state.filtered);
        } catch (err) {
          productGrid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">Không thể tải sản phẩm: ${
            err.message || "lỗi không xác định"
          }</div>`;
        }
      }

      function bindEvents() {
        document.getElementById("search-input").addEventListener("input", (evt) => {
          const keyword = evt.target.value.trim().toLowerCase();
          if (!keyword) {
            state.filtered = [...state.products];
          } else {
            state.filtered = state.products.filter((item) => {
              return (
                item.name?.toLowerCase().includes(keyword) ||
                item.product_id?.toLowerCase().includes(keyword)
              );
            });
          }
          renderProducts(state.filtered);
        });

        document.getElementById("search-clear").addEventListener("click", () => {
          const input = document.getElementById("search-input");
          input.value = "";
          state.filtered = [...state.products];
          renderProducts(state.filtered);
        });

        document.getElementById("btn-refresh-promos").addEventListener("click", fetchPromotions);

        document.getElementById("btn-explore").addEventListener("click", () => {
          document.getElementById("product-grid").scrollIntoView({ behavior: "smooth" });
        });

        document.getElementById("btn-see-promos").addEventListener("click", () => {
          document.getElementById("promo-list").scrollIntoView({ behavior: "smooth" });
        });

        productGrid.addEventListener("click", async (evt) => {
          const action = evt.target.getAttribute("data-action");
          if (!action) return;
          const productId = evt.target.getAttribute("data-id");
          const product = state.products.find((p) => p.product_id === productId);
          if (!product) return;

          if (action === "add") {
            try {
              await App.auth.ensure();
              await App.apiFetch("/api/customer/cart", {
                method: "POST",
                body: JSON.stringify({ product_id: productId, qty: 1 }),
              });
              await App.cart.refresh();
              App.toast("Đã thêm vào giỏ hàng", "success");
            } catch (err) {
              if (err.code !== "AUTH_REQUIRED") {
                App.toast(err.message || "Không thể thêm vào giỏ hàng", "error");
              }
            }
          } else if (action === "view") {
            App.toast(`Sản phẩm ${product.name} đang được yêu thích!`, "success");
          }
        });
      }

      (async () => {
        await App.init("home");
        bindEvents();
        await Promise.all([fetchPromotions(), fetchProducts()]);
      })();