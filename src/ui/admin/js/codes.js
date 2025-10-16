import { api } from "./api.js";

export function initCodes({ state, loadCodes, onChange }) {
  const form = document.getElementById("codeForm");
  const tableBody = document.getElementById("codesTableBody");
  const resetBtn = document.getElementById("codeResetBtn");
  const deleteBtn = document.getElementById("codeDeleteBtn");

  const codeInput = document.getElementById("codeInput");
  const promoSelect = document.getElementById("codePromoId");
  const expireInput = document.getElementById("codeExpire");
  const enabledInput = document.getElementById("codeEnabled");

  let editingCode = null;

  function resetForm() {
    editingCode = null;
    form.reset();
    codeInput.removeAttribute("readonly");
    enabledInput.checked = true;
    deleteBtn.classList.add("d-none");
  }

  function fillForm(record) {
    editingCode = record.code;
    codeInput.value = record.code || "";
    codeInput.setAttribute("readonly", "readonly");
    promoSelect.value = record.promo_id || "";
    expireInput.value = record.expire_date || "";
    enabledInput.checked = !!record.enabled;
    deleteBtn.classList.remove("d-none");
  }

  function renderOptions() {
    if (!promoSelect) return;
    const current = promoSelect.value;
    promoSelect.innerHTML =
      '<option value="">Chọn khuyến mãi</option>' +
      state.promos
        .slice()
        .sort((a, b) => a.promo_id.localeCompare(b.promo_id))
        .map(
          (promo) =>
            `<option value="${promo.promo_id}">${promo.promo_id} - ${
              promo.name || ""
            }</option>`
        )
        .join("");
    if (current) promoSelect.value = current;
  }

  function renderTable() {
    if (!tableBody) return;
    if (!state.codes.length) {
      tableBody.innerHTML =
        '<tr><td colspan="5" class="text-center text-muted">Chưa có mã giảm giá</td></tr>';
      return;
    }

    const rows = state.codes
      .slice()
      .sort((a, b) => a.code.localeCompare(b.code))
      .map((record) => {
        const promo = state.promos.find((p) => p.promo_id === record.promo_id);
        const promoLabel = promo
          ? `${promo.promo_id} - ${promo.name || ""}`
          : record.promo_id;
        const statusBadge = record.enabled
          ? '<span class="badge success">Đang mở</span>'
          : '<span class="badge warning">Đã khóa</span>';
        return `
          <tr data-code="${record.code}">
            <td>${record.code}</td>
            <td>${promoLabel}</td>
            <td>${record.expire_date || ""}</td>
            <td>${statusBadge}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-warning me-2" data-action="edit">Sửa</button>
              <button class="btn btn-sm btn-danger" data-action="delete">Xóa</button>
            </td>
          </tr>
        `;
      })
      .join("");

    tableBody.innerHTML = rows;
  }

  function render() {
    renderOptions();
    renderTable();
  }

  async function refreshAll() {
    await loadCodes();
    renderTable();
    onChange?.();
  }

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      const code = (codeInput.value || "").trim();
      const promoId = promoSelect.value;
      if (!code) {
        alert("Vui lòng nhập mã giảm giá.");
        return;
      }
      if (!promoId) {
        alert("Vui lòng chọn khuyến mãi.");
        return;
      }

      const payload = {
        code,
        promo_id: promoId,
        expire_date: expireInput.value,
        enabled: enabledInput.checked,
      };

      if (editingCode) {
        await api(`/api/codes/${editingCode}`, { method: "PUT", body: payload });
      } else {
        await api("/api/codes", { method: "POST", body: payload });
      }

      resetForm();
      await refreshAll();
    } catch (err) {
      alert(err.message);
    }
  });

  resetBtn?.addEventListener("click", () => {
    resetForm();
  });

  deleteBtn?.addEventListener("click", async () => {
    if (!editingCode) return;
    if (!confirm("Bạn có chắc muốn xóa mã giảm giá này?")) return;
    try {
      await api(`/api/codes/${editingCode}`, { method: "DELETE" });
      resetForm();
      await refreshAll();
    } catch (err) {
      alert(err.message);
    }
  });

  tableBody?.addEventListener("click", async (event) => {
    const actionBtn = event.target.closest("button[data-action]");
    if (!actionBtn) return;
    const row = actionBtn.closest("tr");
    const code = row?.dataset.code;
    if (!code) return;
    const record = state.codes.find((item) => item.code === code);
    if (!record) return;

    const action = actionBtn.dataset.action;
    if (action === "edit") {
      fillForm(record);
    }
    if (action === "delete") {
      if (!confirm("Bạn có chắc muốn xóa mã giảm giá này?")) return;
      try {
        await api(`/api/codes/${code}`, { method: "DELETE" });
        if (editingCode === code) resetForm();
        await refreshAll();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  return { render, resetForm, renderOptions };
}
