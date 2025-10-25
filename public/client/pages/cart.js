import { App, formatCurrency } from "../assets/app.js";

      const state = {
        items: [],
        enriched: [],
        totals: { subtotal: 0, discount: 0, total: 0, count: 0 },
        promo: null,
        couponMessage: "",
      };

      const cartItemsEl = document.getElementById("cart-items");
      const cartCountEl = document.getElementById("cart-count");
      const summarySubtotal = document.getElementById("summary-subtotal");
      const summaryDiscount = document.getElementById("summary-discount");
      const summaryTotal = document.getElementById("summary-total");
      const couponStatus = document.getElementById("coupon-status");

      function toDateKey(value) {
        if (!value) return "";
        if (typeof value === "string") return value.slice(0, 10);
        if (value instanceof Date) return value.toISOString().slice(0, 10);
        if (value.year && value.month && value.day) {
          const pad = (n) => String(n).padStart(2, "0");
          return `${value.year}-${pad(value.month)}-${pad(value.day)}`;
        }
        try {
          return new Date(value).toISOString().slice(0, 10);
        } catch {
          return "";
        }
      }

      function renderSummary() {
        cartCountEl.textContent = `${state.totals.count || 0} mÃ³n`;
        summarySubtotal.textContent = formatCurrency(state.totals.subtotal || 0);
        summaryDiscount.textContent = "-" + formatCurrency(state.totals.discount || 0);
        summaryTotal.textContent = formatCurrency(state.totals.total || 0);
      }

      function renderEmpty() {
        cartItemsEl.innerHTML = `
          <div class="empty-state">
            <h3 style="margin-bottom: 8px">Giá» hÃ ng Ä‘ang trá»‘ng</h3>
            <p class="muted">HÃ£y thÃªm sáº£n pháº©m yÃªu thÃ­ch Ä‘á»ƒ báº¯t Ä‘áº§u Ä‘Æ¡n hÃ ng má»›i.</p>
            <a class="btn-primary" style="display:inline-flex;margin-top:14px" href="/">
              KhÃ¡m phÃ¡ Æ°u Ä‘Ã£i ngay
            </a>
          </div>
        `;
      }

      function renderCart() {
        if (!state.enriched.length) {
          renderEmpty();
          return;
        }

        cartItemsEl.innerHTML = "";
        state.enriched.forEach((item) => {
          const row = document.createElement("div");
          row.className = "cart-item";
          row.innerHTML = `
            <div class="cart-item__media">
              <img src="${item.product?.image_url || `https://images.promoshop.vn/placeholder/${item.product_id}.jpg`}" alt="${item.product?.name || item.product_id}" />
            </div>
            <div class="cart-item__info">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                <div>
                  <h3 style="margin:0">${item.product?.name || item.product_id}</h3>
                  <div class="muted" style="font-size:13px;margin-top:4px">${item.product?.category || "Thá»©c uá»‘ng"}</div>
                </div>
                <button class="btn-ghost" data-action="remove" data-id="${item.item_id}" data-added="${toDateKey(item.added_at)}">XÃ³a</button>
              </div>
              <div style="margin-top:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                  <div class="muted" style="font-size:13px">GiÃ¡ Æ°u Ä‘Ã£i</div>
                  <div style="font-size:18px;font-weight:700">${formatCurrency(item.final_price)}</div>
                  ${
                    item.final_price !== item.base_price
                      ? `<div class="muted" style="text-decoration:line-through;font-size:13px">${formatCurrency(item.base_price)}</div>`
                      : ""
                  }
                </div>
                <div>
                  <div class="muted" style="font-size:13px;margin-bottom:4px">Sá»‘ lÆ°á»£ng</div>
                  <div class="qty-control">
                    <button data-action="decrease" data-id="${item.item_id}" data-added="${toDateKey(item.added_at)}">-</button>
                    <input type="text" value="${item.qty}" readonly />
                    <button data-action="increase" data-id="${item.item_id}" data-added="${toDateKey(item.added_at)}">+</button>
                  </div>
                </div>
                <div>
                  <div class="muted" style="font-size:13px">ThÃ nh tiá»n</div>
                  <div style="font-size:18px;font-weight:700">${formatCurrency(item.line_total)}</div>
                  ${
                    item.discount
                      ? `<div class="badge success" style="margin-top:6px">Tiáº¿t kiá»‡m ${formatCurrency(item.discount)}</div>`
                      : ""
                  }
                </div>
              </div>
            </div>
          `;
          cartItemsEl.appendChild(row);
        });
      }

      async function loadCart(showLoading = true) {
        if (showLoading) {
          cartItemsEl.innerHTML = '<div class="muted" style="padding:20px">Äang táº£i giá» hÃ ng...</div>';
        }
        try {
          const res = await App.apiFetch("/api/customer/cart");
          state.items = res.data || [];
          state.enriched = res.enriched || [];
          state.totals = res.totals || { subtotal: 0, discount: 0, total: 0, count: 0 };
          renderCart();
          renderSummary();
          App.cart.setCount(state.totals.count || 0);
        } catch (err) {
          cartItemsEl.innerHTML = `<div class="empty-state">KhÃ´ng thá»ƒ táº£i giá» hÃ ng: ${err.message || "lá»—i khÃ´ng xÃ¡c Ä‘á»‹nh"}</div>`;
        }
      }

      async function updateQuantity(itemId, addedAt, delta) {
        const item = state.enriched.find(
          (row) => row.item_id === itemId && toDateKey(row.added_at) === addedAt
        );
        if (!item) return;
        const nextQty = Math.max(1, item.qty + delta);
        if (nextQty === item.qty) return;

        try {
          await App.apiFetch("/api/customer/cart", {
            method: "PATCH",
            body: JSON.stringify({ item_id: itemId, added_at: addedAt, qty: nextQty }),
          });
          App.toast("ÄÃ£ cáº­p nháº­t sá»‘ lÆ°á»£ng", "success");
          await loadCart(false);
        } catch (err) {
          App.toast(err.message || "KhÃ´ng thá»ƒ cáº­p nháº­t sá»‘ lÆ°á»£ng", "error");
        }
      }

      async function removeItem(itemId, addedAt) {
        const confirmed = confirm("Báº¡n cÃ³ cháº¯c cháº¯n muá»‘n xÃ³a sáº£n pháº©m nÃ y khá»i giá»?");
        if (!confirmed) return;
        try {
          await App.apiFetch("/api/customer/cart", {
            method: "DELETE",
            body: JSON.stringify({ item_id: itemId, added_at: addedAt }),
          });
          App.toast("ÄÃ£ xÃ³a khá»i giá» hÃ ng", "success");
          await loadCart(false);
        } catch (err) {
          App.toast(err.message || "KhÃ´ng thá»ƒ xÃ³a sáº£n pháº©m", "error");
        }
      }

      async function applyCoupon() {
        const code = document.getElementById("coupon-input").value.trim();
        if (!code) {
          couponStatus.textContent = "Vui lÃ²ng nháº­p mÃ£ Æ°u Ä‘Ã£i trÆ°á»›c.";
          return;
        }
        try {
          await App.auth.ensure();
          const res = await App.apiFetch("/api/customer/apply-code", {
            method: "POST",
            body: JSON.stringify({ code }),
          });
          state.promo = res.promo;
          couponStatus.textContent = `Ãp dá»¥ng thÃ nh cÃ´ng: ${res.promo.promo_id}`;
          couponStatus.style.color = "#10b981";
        } catch (err) {
          if (err.code !== "AUTH_REQUIRED") {
            couponStatus.textContent = err.message || "KhÃ´ng thá»ƒ Ã¡p dá»¥ng mÃ£.";
            couponStatus.style.color = "#ef4444";
          }
        }
      }

      function bindEvents() {
        document.getElementById("btn-checkout").addEventListener("click", async () => {
          try {
            await App.auth.ensure();
            if (!state.enriched.length) {
              App.toast("Giá» hÃ ng Ä‘ang trá»‘ng", "error");
              return;
            }
            window.location.href = "/checkout";
          } catch (err) {
            if (err.code !== "AUTH_REQUIRED") {
              App.toast(err.message || "KhÃ´ng thá»ƒ tiáº¿p tá»¥c thanh toÃ¡n", "error");
            }
          }
        });

        document.getElementById("coupon-apply").addEventListener("click", applyCoupon);

        cartItemsEl.addEventListener("click", async (evt) => {
          const target = evt.target;
          const action = target.getAttribute("data-action");
          if (!action) return;
          const itemId = target.getAttribute("data-id");
          const addedAt = target.getAttribute("data-added");
          if (!itemId || !addedAt) return;

          if (action === "increase") {
            await updateQuantity(itemId, addedAt, 1);
          } else if (action === "decrease") {
            await updateQuantity(itemId, addedAt, -1);
          } else if (action === "remove") {
            await removeItem(itemId, addedAt);
          }
        });
      }

      (async () => {
        await App.init("cart");
        bindEvents();
        try {
          await App.auth.ensure();
          await loadCart();
        } catch (err) {
          if (err.code === "AUTH_REQUIRED") {
            cartItemsEl.innerHTML = `
              <div class="empty-state">
                <h3 style="margin-bottom: 8px">Báº¡n chÆ°a Ä‘Äƒng nháº­p</h3>
                <p class="muted">ÄÄƒng nháº­p Ä‘á»ƒ quáº£n lÃ½ giá» hÃ ng vÃ  tiáº¿p tá»¥c thanh toÃ¡n.</p>
                <button class="btn-primary" style="margin-top:12px" id="btn-login-now">ÄÄƒng nháº­p</button>
              </div>
            `;
            document.getElementById("btn-login-now").addEventListener("click", () => {
              App.modals.login();
            });
          } else {
            cartItemsEl.innerHTML = `<div class="empty-state">KhÃ´ng thá»ƒ táº£i giá» hÃ ng: ${err.message || "lá»—i khÃ´ng xÃ¡c Ä‘á»‹nh"}</div>`;
          }
        }
      })();
