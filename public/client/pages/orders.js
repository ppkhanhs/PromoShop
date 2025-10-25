import { App, formatCurrency } from "../assets/app.js";

      const listEl = document.getElementById("order-list");
      const state = {
        orders: [],
        details: new Map(),
        loading: false,
      };

      function formatDate(value) {
        if (!value) return "";
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return "";
        return date.toLocaleString("vi-VN", { hour12: false });
      }

      function statusLabel(status) {
        switch (status) {
          case "pending_confirmation":
            return "Chá» xÃ¡c nháº­n";
          case "pending_payment":
            return "Chá» thanh toÃ¡n";
          case "processing":
            return "Äang xá»­ lÃ½";
          case "shipping":
            return "Äang giao";
          case "completed":
            return "HoÃ n thÃ nh";
          case "cancelled":
            return "ÄÃ£ há»§y";
          default:
            return "Äang cáº­p nháº­t";
        }
      }

      function renderOrders() {
        if (!state.orders.length) {
          listEl.innerHTML = `
            <div class="empty-state">
              <h3 style="margin-bottom:8px">Báº¡n chÆ°a cÃ³ Ä‘Æ¡n hÃ ng nÃ o</h3>
              <p class="muted">Báº¯t Ä‘áº§u mua sáº¯m Ä‘á»ƒ nháº­n Æ°u Ä‘Ã£i má»›i nháº¥t nhÃ©!</p>
              <a class="btn-primary" style="display:inline-flex;margin-top:12px" href="/">
                KhÃ¡m phÃ¡ Æ°u Ä‘Ã£i
              </a>
            </div>
          `;
          return;
        }

        listEl.innerHTML = "";
        state.orders.forEach((order) => {
          const card = document.createElement("article");
          card.className = "order-card";
          card.innerHTML = `
            <div class="order-meta">
              <div>
                <strong>${order.order_id}</strong>
                <div class="muted" style="font-size:13px">NgÃ y Ä‘áº·t: ${formatDate(order.order_date)}</div>
              </div>
              <span class="status-badge ${order.status}">${statusLabel(order.status)}</span>
            </div>
            <div class="mini-item">
              <span>Thanh toÃ¡n</span>
              <span class="muted" style="text-transform:uppercase">${order.payment_method}</span>
            </div>
            <div class="mini-item">
              <span>Tá»•ng tiá»n</span>
              <strong>${formatCurrency(order.final_amount)}</strong>
            </div>
            <div class="card-actions">
              <button class="btn-outline" data-action="toggle" data-id="${order.order_id}">
                Theo dÃµi Ä‘Æ¡n
              </button>
            </div>
            <div class="timeline" id="timeline-${order.order_id}" style="display:none"></div>
          `;
          listEl.appendChild(card);
        });
      }

      function renderTimeline(orderId, detail) {
        const container = document.getElementById(`timeline-${orderId}`);
        if (!container) return;
        if (!detail) {
          container.innerHTML =
            '<div class="muted" style="padding-left:12px">ChÆ°a cÃ³ dá»¯ liá»‡u tráº¡ng thÃ¡i.</div>';
          return;
        }

        const items = detail.timeline || [];
        const order = detail.order;
        const products = detail.items || [];

        const productLines = products
          .map(
            (item) => `
              <div class="mini-item">
                <span>${item.name || item.product_id} Ã— ${item.qty}</span>
                <span>${formatCurrency(item.line_total || 0)}</span>
              </div>
            `
          )
          .join("");

        const timelineLines = items
          .map(
            (entry) => `
              <div class="timeline-item">
                <div><strong>${statusLabel(entry.status)}</strong></div>
                <div class="muted" style="font-size:13px">${formatDate(entry.changed_at)}</div>
                ${entry.note ? `<div style="font-size:13px">${entry.note}</div>` : ""}
              </div>
            `
          )
          .join("");

        container.innerHTML = `
          <div class="order-card" style="border:none;border-left:3px solid rgba(24,119,242,0.12);box-shadow:none;padding-left:16px">
            <div class="mini-list">
              <div class="mini-item">
                <span>Äá»‹a chá»‰ giao</span>
                <span class="muted" style="max-width:220px;text-align:right">${order.shipping_address || "â€”"}</span>
              </div>
              <div class="mini-item">
                <span>NgÆ°á»i nháº­n</span>
                <span class="muted">${order.contact_name || "â€”"} (${order.contact_phone || "â€”"})</span>
              </div>
              <div class="mini-item">
                <span>Táº¡m tÃ­nh</span>
                <span>${formatCurrency(order.total_amount)}</span>
              </div>
              <div class="mini-item">
                <span>Giáº£m giÃ¡</span>
                <span>-${formatCurrency(order.discount_amount)}</span>
              </div>
              <div class="mini-item">
                <span>ThÃ nh tiá»n</span>
                <strong>${formatCurrency(order.final_amount)}</strong>
              </div>
            </div>
          </div>
          <h3 style="margin:12px 0 8px">Sáº£n pháº©m</h3>
          <div class="mini-list">${productLines}</div>
          <h3 style="margin:12px 0 8px">Tiáº¿n trÃ¬nh Ä‘Æ¡n hÃ ng</h3>
          <div class="timeline">${timelineLines || '<div class="muted">ChÆ°a cÃ³ lá»‹ch sá»­.</div>'}</div>
        `;
      }

      async function loadOrders() {
        listEl.innerHTML = `
          <div class="empty-state">
            <h3 style="margin-bottom:8px">Äang táº£i dá»¯ liá»‡u Ä‘Æ¡n hÃ ng...</h3>
            <p class="muted">Vui lÃ²ng Ä‘á»£i trong giÃ¢y lÃ¡t.</p>
          </div>
        `;
        try {
          const res = await App.apiFetch("/api/customer/orders");
          state.orders = (res.data || []).sort((a, b) => {
            const d1 = new Date(a.order_date || 0).getTime();
            const d2 = new Date(b.order_date || 0).getTime();
            return d2 - d1;
          });
          renderOrders();
        } catch (err) {
          listEl.innerHTML = `<div class="empty-state">KhÃ´ng thá»ƒ táº£i Ä‘Æ¡n hÃ ng: ${
            err.message || "lá»—i khÃ´ng xÃ¡c Ä‘á»‹nh"
          }</div>`;
        }
      }

      async function loadOrderDetail(orderId) {
        if (state.details.has(orderId)) {
          return state.details.get(orderId);
        }
        try {
          const res = await App.apiFetch(`/api/customer/orders/${orderId}`);
          state.details.set(orderId, res);
          return res;
        } catch (err) {
          App.toast(err.message || "KhÃ´ng thá»ƒ táº£i chi tiáº¿t Ä‘Æ¡n hÃ ng", "error");
          return null;
        }
      }

      function bindEvents() {
        listEl.addEventListener("click", async (evt) => {
          const btn = evt.target.closest("button[data-action='toggle']");
          if (!btn) return;
          const orderId = btn.getAttribute("data-id");
          const timeline = document.getElementById(`timeline-${orderId}`);
          if (!timeline) return;

          if (timeline.dataset.loaded !== "true") {
            timeline.innerHTML = '<div class="muted">Äang táº£i chi tiáº¿t...</div>';
            const detail = await loadOrderDetail(orderId);
            renderTimeline(orderId, detail);
            timeline.dataset.loaded = "true";
            timeline.style.display = "grid";
            btn.textContent = "Thu gá»n";
          } else {
            const isVisible = timeline.style.display !== "none";
            timeline.style.display = isVisible ? "none" : "grid";
            btn.textContent = isVisible ? "Theo dÃµi Ä‘Æ¡n" : "Thu gá»n";
          }
        });
      }

      (async () => {
        await App.init("orders");
        bindEvents();
        try {
          await App.auth.ensure();
          await loadOrders();
        } catch (err) {
          if (err.code === "AUTH_REQUIRED") {
            listEl.innerHTML = `
              <div class="empty-state">
                <h3 style="margin-bottom:8px">Báº¡n cáº§n Ä‘Äƒng nháº­p</h3>
                <p class="muted">ÄÄƒng nháº­p Ä‘á»ƒ xem lá»‹ch sá»­ vÃ  theo dÃµi Ä‘Æ¡n hÃ ng cá»§a báº¡n.</p>
                <button class="btn-primary" id="btn-login-orders" style="margin-top:12px">ÄÄƒng nháº­p</button>
              </div>
            `;
            document.getElementById("btn-login-orders").addEventListener("click", () => {
              App.modals.login();
            });
          } else {
            listEl.innerHTML = `<div class="empty-state">KhÃ´ng thá»ƒ táº£i Ä‘Æ¡n hÃ ng: ${
              err.message || "lá»—i khÃ´ng xÃ¡c Ä‘á»‹nh"
            }</div>`;
          }
        }
      })();
