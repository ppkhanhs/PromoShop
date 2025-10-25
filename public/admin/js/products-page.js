import { initAdmin } from './app.js';
import { state, loadProducts, loadPromoProducts } from './state.js';
import { initProducts } from './products.js';

document.addEventListener('DOMContentLoaded', async () => {
  await initAdmin({ activeNav: 'products' });

  let productsModule;

  const loadProductsAndRender = async () => {
    await loadProducts();
    productsModule.render();
  };

  productsModule = initProducts({
    state,
    loadProducts: loadProductsAndRender,
    loadPromoProducts,
    onChange: () => {},
  });

  await Promise.all([loadProducts(), loadPromoProducts()]);
  productsModule.render();
});
