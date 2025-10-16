import { loadLayout } from "./layout.js";
import { state, loadPromos, loadAccounts, loadOrders } from "./state.js";

document.addEventListener("DOMContentLoaded", async () => {
  await loadLayout();

  const promoFilter = document.getElementById("orderPromoFilter");
  const customerInput = document.getElementById("orderCustomerFilter");
  const filterBtn = document.getElementById("orderFilterBtn");
  const clearBtn = document.getElementById("orderClearBtn");
  const tableBody = document.getElementById("ordersTableBody");

  const formatCurrency = (value) => {
    const number = Number(value ?? 0);
    return number.toLocaleString("vi-VN", { style: "currency", currency: "VND" });
  };

  const renderPromos = () => {
    if (!promoFilter) return;
    const current = promoFilter.value;
    promoFilter.innerHTML = '<option value="">Tat ca</option>' +
      state.promos
        .slice()
        .sort((a, b) => a.promo_id.localeCompare(b.promo_id))
        .map(
          (promo) => `<option value="${promo.promo_id}">${promo.promo_id} - ${promo.name || ""}</option>`
        )
        .join("");
    if (current) promoFilter.value = current;
  };

  const renderTable = () => {
    if (!tableBody) return;
    if (!state.orders.length) {
      tableBody.innerHTML = '<tr><td colspan="9" class="text-center text-muted">Chua co don hang</td></tr>';
      return;
    }

    const promoMap = new Map(state.promos.map((p) => [p.promo_id, p]));
    const accountMap = new Map(state.accounts.map((acc) => [acc.phone, acc.full_name || ""]));

    const rows = state.orders
      .slice()
      .sort((a, b) => {
        const dateA = a.order_date || "";
        const dateB = b.order_date || "";
        return String(dateB).localeCompare(String(dateA));
      })
      .map((order) => {
        const promo = order.promo_id ? promoMap.get(order.promo_id) : null;
        const delta = Math.abs(
          Number(order.total_amount ?? 0) -
            Number(order.discount_amount ?? 0) -
            Number(order.final_amount ?? 0)
        );
        const applied = order.promo_id && Number(order.discount_amount ?? 0) > 0;
        let statusText = "Khong ap dung";
        let badgeClass = "badge";
        if (applied) {
          if (delta < 1e-3) {
            statusText = "Hop le";
            badgeClass = "badge success";
          } else {
            statusText = "Can kiem tra";
            badgeClass = "badge warning";
          }
        }

        const promoLabel = promo
          ? `${promo.promo_id} - ${promo.name || ""}`
          : order.promo_id || "-";

        return `
          <tr>
            <td>${order.order_id}</td>
            <td>${order.customer_id || ""}</td>
            <td>${accountMap.get(order.customer_id) || ""}</td>
            <td>${promoLabel}</td>
            <td>${formatCurrency(order.total_amount)}</td>
            <td>${formatCurrency(order.discount_amount)}</td>
            <td>${formatCurrency(order.final_amount)}</td>
            <td><span class="${badgeClass}">${statusText}</span></td>
            <td>${order.order_date || ""}</td>
          </tr>
        `;
      })
      .join("");

    tableBody.innerHTML = rows;
  };

  const applyFilters = async () => {
    const promoId = promoFilter?.value || "";
    const customerId = (customerInput?.value || "").trim();
    const query = {};
    if (promoId) query.promo_id = promoId;
    if (customerId) query.customer_id = customerId;
    await loadAccounts();
    await loadOrders(query);
    renderTable();
  };

  filterBtn?.addEventListener("click", async (event) => {
    event.preventDefault();
    await applyFilters();
  });

  promoFilter?.addEventListener("change", async () => {
    await applyFilters();
  });

  clearBtn?.addEventListener("click", async (event) => {
    event.preventDefault();
    if (promoFilter) promoFilter.value = "";
    if (customerInput) customerInput.value = "";
    await loadAccounts();
    await loadOrders();
    renderTable();
  });

  await loadPromos();
  renderPromos();
  await loadAccounts();
  await loadOrders();
  renderTable();
});
