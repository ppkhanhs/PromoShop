import { initAdmin } from './app.js';
import { state, loadPromos, loadCodes } from './state.js';
import { initCodes } from './codes.js';

document.addEventListener('DOMContentLoaded', async () => {
  await initAdmin({ activeNav: 'codes' });

  let codesModule;

  const loadCodesAndRender = async () => {
    await loadCodes();
    codesModule.render();
  };

  codesModule = initCodes({
    state,
    loadCodes: loadCodesAndRender,
    onChange: () => {},
  });

  await Promise.all([loadPromos(), loadCodes()]);
  codesModule.render();
});
