import { loadLayout } from "./layout.js";
import { state, loadPromos, loadCodes } from "./state.js";
import { initCodes } from "./codes.js";

document.addEventListener("DOMContentLoaded", async () => {
  await loadLayout();

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
