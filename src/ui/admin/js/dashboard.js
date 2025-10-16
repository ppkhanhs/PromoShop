export function initDashboard(state) {
  const promosEl = document.getElementById("countPromos");
  const productsEl = document.getElementById("countProducts");
  const codesEl = document.getElementById("countCodes");
  const ordersEl = document.getElementById("countOrders");

  function render() {
    if (promosEl) promosEl.textContent = state.promos.length ?? 0;
    if (productsEl) productsEl.textContent = state.products.length ?? 0;
    if (codesEl) codesEl.textContent = state.codes.length ?? 0;
    if (ordersEl) ordersEl.textContent = state.orders.length ?? 0;
  }

  return { render };
}
