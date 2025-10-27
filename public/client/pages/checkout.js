import { App, formatCurrency } from "../assets/app.js";

      const summaryBox = document.getElementById("order-summary");
      const form = document.getElementById("checkout-form");
      const submitBtn = document.getElementById("submit-order");

      const state = {
        cart: null,
        account: null,
        loading: false,
      };

      function renderSummary() {
        if (!state.cart || !state.cart.enriched.length) {
          summaryBox.innerHTML = `
            <div class="empty-state">
              <h3 style="margin-bottom:8px">Giá» hÃ ng cá»§a báº¡n Ä‘ang trá»‘ng</h3>
              <p class="muted">Vui lÃ²ng thÃªm sáº£n pháº©m trÆ°á»›c khi thanh toÃ¡n.</p>
              <a class="btn-primary" style="display:inline-flex;margin-top:12px" href="/">
                Quay láº¡i mua sáº¯m
              </a>
            </div>
          `;
          submitBtn.disabled = true;
          return;
        }

        submitBtn.disabled = false;
        const itemsList = state.cart.enriched
          .map(
            (item) => `
              <div class="order-card" style="box-shadow:none;border-color:rgba(24,119,242,0.05)">
                <div class="order-meta">
                  <div>
                    <strong>${item.product?.name || item.product_id}</strong>
                    <div class="muted" style="font-size:13px">
                      Sá»‘ lÆ°á»£ng: ${item.qty} â€¢ ${item.product?.category || "Äá»“ uá»‘ng"}
                    </div>
                  </div>
                  <span>${formatCurrency(item.line_total)}</span>
                </div>
              </div>
            `
          )
          .join("");

        summaryBox.innerHTML = `
          <div class="mini-list" style="gap:12px">${itemsList}</div>
          <hr />
          <div class="summary-row">
            <span>Táº¡m tÃ­nh</span>
            <span>${formatCurrency(state.cart.totals.subtotal)}</span>
          </div>
          <div class="summary-row">
            <span>Khuyáº¿n mÃ£i</span>
            <span>-${formatCurrency(state.cart.totals.discount)}</span>
          </div>
          <div class="summary-row">
            <span>PhÃ­ váº­n chuyá»ƒn</span>
            <span>0 Ä‘</span>
          </div>
          <div class="summary-row summary-total">
            <span>ThÃ nh tiá»n</span>
            <span>${formatCurrency(state.cart.totals.total)}</span>
          </div>
        `;
      }

      async function loadCart() {
        summaryBox.innerHTML = '<div class="muted">Äang táº£i giá» hÃ ng...</div>';
        try {
          const res = await App.apiFetch("/api/customer/cart");
          state.cart = res;
          renderSummary();
        } catch (err) {
          summaryBox.innerHTML = `<div class="empty-state">KhÃ´ng thá»ƒ táº£i giá» hÃ ng: ${
            err.message || "lá»—i khÃ´ng xÃ¡c Ä‘á»‹nh"
          }</div>`;
          submitBtn.disabled = true;
        }
      }

      function fillAccountInfo() {
        const account = App.auth.account();
        if (!account) return;
        state.account = account;
        const nameInput = document.getElementById("customer-name");
        const phoneInput = document.getElementById("customer-phone");
        if (!nameInput.value) nameInput.value = account.full_name || "";
        if (!phoneInput.value) phoneInput.value = account.phone || "";
      }

      function setLoading(loading) {
        state.loading = loading;
        submitBtn.disabled = loading;
        submitBtn.textContent = loading ? "Äang xá»­ lÃ½..." : "XÃ¡c nháº­n Ä‘áº·t hÃ ng";
      }

      function bindEvents() {
        form.addEventListener("submit", async (evt) => {
          evt.preventDefault();
          if (!state.cart || !state.cart.enriched.length) {
            App.toast("Giá» hÃ ng trá»‘ng, khÃ´ng thá»ƒ thanh toÃ¡n", "error");
            return;
          }

          const payload = {
            name: document.getElementById("customer-name").value.trim(),
            phone: document.getElementById("customer-phone").value.trim(),
            address: document.getElementById("customer-address").value.trim(),
            note: document.getElementById("customer-note").value.trim(),
            payment: document.getElementById("payment-method").value,
          };

          if (!payload.name || !payload.phone || !payload.address) {
            App.toast("Vui lÃ²ng Ä‘iá»n Ä‘áº§y Ä‘á»§ thÃ´ng tin giao hÃ ng", "error");
            return;
          }

          try {
            setLoading(true);
            const res = await App.apiFetch("/api/customer/checkout", {
              method: "POST",
              body: JSON.stringify(payload),
            });
            App.toast("Äáº·t hÃ ng thÃ nh cÃ´ng! Äang chuyá»ƒn hÆ°á»›ng...", "success");
            await App.cart.refresh();
            setTimeout(() => {
              window.location.href = `/orders?order=${res.order?.order_id || ""}`;
            }, 1200);
          } catch (err) {
            App.toast(err.message || "KhÃ´ng thá»ƒ táº¡o Ä‘Æ¡n hÃ ng", "error");
          } finally {
            setLoading(false);
          }
        });
      }

      (async () => {
        await App.init("cart");
        bindEvents();
        try {
          await App.auth.ensure();
          fillAccountInfo();
          await loadCart();
        } catch (err) {
          if (err.code === "AUTH_REQUIRED") {
            summaryBox.innerHTML = `
              <div class="empty-state">
                <h3 style="margin-bottom:8px">Báº¡n cáº§n Ä‘Äƒng nháº­p</h3>
                <p class="muted">Vui lÃ²ng Ä‘Äƒng nháº­p Ä‘á»ƒ tiáº¿p tá»¥c quy trÃ¬nh thanh toÃ¡n.</p>
                <button class="btn-primary" id="btn-open-login" style="margin-top:12px">ÄÄƒng nháº­p</button>
              </div>
            `;
            document.getElementById("btn-open-login").addEventListener("click", () => {
              App.modals.login();
            });
            submitBtn.disabled = true;
          } else {
            summaryBox.innerHTML = `<div class="empty-state">CÃ³ lá»—i xáº£y ra: ${
              err.message || "lá»—i khÃ´ng xÃ¡c Ä‘á»‹nh"
            }</div>`;
            submitBtn.disabled = true;
          }
        }
      })();
