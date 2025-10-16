import { loadLayout } from "./layout.js";
import { state, loadProducts, loadPromoProducts } from "./state.js";
import { initProducts } from "./products.js";

document.addEventListener("DOMContentLoaded", async () => {
  await loadLayout();

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
