import { initAdmin } from '../app.js';
import { state, loadPromos, loadPromoProducts, loadCodes } from '../state.js';
import { initPromos } from '../promos.js';

document.addEventListener('DOMContentLoaded', async () => {
  await initAdmin({ activeNav: 'promos' });

  let promosModule;

  const loadPromosAndRender = async () => {
    await loadPromos();
    promosModule.render();
  };

  promosModule = initPromos({
    state,
    loadPromos: loadPromosAndRender,
    loadPromoProducts,
    loadCodes,
    onChange: () => {},
  });

  await Promise.all([loadPromos(), loadPromoProducts(), loadCodes()]);
  promosModule.render();
});
