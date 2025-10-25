import { initAdmin } from './app.js';
import { state, loadPromos, loadProducts, loadCodes, loadOrders } from './state.js';
import { initDashboard } from './dashboard.js';

document.addEventListener('DOMContentLoaded', async () => {
  await initAdmin({ activeNav: 'dashboard' });
  const dashboardModule = initDashboard(state);
  await Promise.all([loadPromos(), loadProducts(), loadCodes(), loadOrders()]);
  dashboardModule.render();
});
