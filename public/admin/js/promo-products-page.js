import { initAdmin } from './app.js';
import { state, loadPromos, loadProducts, loadPromoProducts } from './state.js';
import { initPromoProducts } from './promo-products.js';

document.addEventListener('DOMContentLoaded', async () => {
  await initAdmin({ activeNav: 'promo-products' });

  let promoProductsModule;

  const loadPromoProductsAndRender = async () => {
    await loadPromoProducts();
    promoProductsModule.render();
  };

  promoProductsModule = initPromoProducts({
    state,
    loadPromoProducts: loadPromoProductsAndRender,
    onChange: () => {},
  });

  await Promise.all([loadPromos(), loadProducts(), loadPromoProducts()]);
  promoProductsModule.render();
});
