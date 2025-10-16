import { api } from "./api.js";

const PROMO_TYPES = [
  { key: "percent", label: "Giam gia %", dbValue: "Giảm giá %" },
  { key: "fixed", label: "Giam gia tien", dbValue: "Giảm giá tiền" },
  { key: "gift", label: "Tang kem", dbValue: "Tặng quà" },
];

const findTypeByKey = (key) => PROMO_TYPES.find((item) => item.key === key);
const findTypeByDb = (value) =>
  PROMO_TYPES.find((item) => item.dbValue === value) || PROMO_TYPES[0];

const ensureTypeOptions = (select) => {
  if (!select) return;
  select.innerHTML = PROMO_TYPES.map(
    (item) => `<option value="${item.key}">${item.label}</option>`
  ).join("");
};

const formatTypeLabel = (value) => {
  const type = findTypeByDb(value);
  return type ? type.label : value || "";
};

export function initPromos({
  state,
  loadPromos,
  loadPromoProducts,
  loadCodes,
  onChange,
}) {
  const form = document.getElementById("promoForm");
  const tableBody = document.getElementById("promoTableBody");
  const resetBtn = document.getElementById("promoResetBtn");
  const deleteBtn = document.getElementById("promoDeleteBtn");

  const promoIdInput = document.getElementById("promo_id");
  const nameInput = document.getElementById("name");
  const typeSelect = document.getElementById("type");
  const startDateInput = document.getElementById("start_date");
  const endDateInput = document.getElementById("end_date");
  const descriptionInput = document.getElementById("description");
  const minOrderInput = document.getElementById("min_order_amount");
  const limitInput = document.getElementById("limit_per_customer");
  const quotaInput = document.getElementById("global_quota");
  const channelsInput = document.getElementById("channels");
  const stackableInput = document.getElementById("stackable");

  ensureTypeOptions(typeSelect);

  let editingId = null;

  function buildPayload() {
    const selectedType = findTypeByKey(typeSelect?.value) || PROMO_TYPES[0];
    const channels = (channelsInput.value || "")
      .split(",")
      .map((s) => s.trim())
      .filter(Boolean);

    return {
      promo_id: (promoIdInput.value || "").trim(),
      name: (nameInput.value || "").trim(),
      type: selectedType.dbValue,
      start_date: startDateInput.value,
      end_date: endDateInput.value,
      description: (descriptionInput.value || "").trim(),
      min_order_amount: Number(minOrderInput.value || 0),
      limit_per_customer: Number(limitInput.value || 0),
      global_quota:
        quotaInput.value === "" ? null : Number(quotaInput.value || 0),
      channels: channels.length ? channels : ["online"],
      stackable: stackableInput.checked,
    };
  }

  function resetForm() {
    editingId = null;
    form.reset();
    promoIdInput.removeAttribute("readonly");
    deleteBtn.disabled = true;
    ensureTypeOptions(typeSelect);
  }

  function fillForm(promo) {
    editingId = promo.promo_id;
    promoIdInput.value = promo.promo_id || "";
    promoIdInput.setAttribute("readonly", "readonly");
    nameInput.value = promo.name || "";

    const type = findTypeByDb(promo.type);
    ensureTypeOptions(typeSelect);
    typeSelect.value = type.key;

    startDateInput.value = promo.start_date || "";
    endDateInput.value = promo.end_date || "";
    descriptionInput.value = promo.description || "";
    minOrderInput.value =
      promo.min_order_amount !== null && promo.min_order_amount !== undefined
        ? Number(promo.min_order_amount)
        : "";
    limitInput.value =
      promo.limit_per_customer !== null &&
      promo.limit_per_customer !== undefined
        ? Number(promo.limit_per_customer)
        : "";
    quotaInput.value =
      promo.global_quota !== null && promo.global_quota !== undefined
        ? Number(promo.global_quota)
        : "";
    channelsInput.value = Array.isArray(promo.channels)
      ? promo.channels.join(", ")
      : "";
    stackableInput.checked = !!promo.stackable;
    deleteBtn.disabled = false;
  }

  function render() {
    if (!tableBody) return;
    if (!state.promos.length) {
      tableBody.innerHTML =
        '<tr><td colspan="7" class="text-center text-muted">Chua co khuyen mai</td></tr>';
      return;
    }

    const rows = state.promos
      .slice()
      .sort((a, b) => a.promo_id.localeCompare(b.promo_id))
      .map((promo) => {
        const stackableBadge = promo.stackable
          ? '<span class="badge success">Co</span>'
          : '<span class="badge warning">Khong</span>';
        return `
          <tr data-id="${promo.promo_id}">
            <td>${promo.promo_id}</td>
            <td>${promo.name || ""}</td>
            <td>${formatTypeLabel(promo.type)}</td>
            <td>${promo.start_date || ""}</td>
            <td>${promo.end_date || ""}</td>
            <td>${stackableBadge}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-warning me-2" data-action="edit">Sua</button>
              <button class="btn btn-sm btn-danger" data-action="delete">Xoa</button>
            </td>
          </tr>
        `;
      })
      .join("");

    tableBody.innerHTML = rows;
  }

  async function refreshAll() {
    await loadPromos();
    render();
    await Promise.all([loadPromoProducts(), loadCodes()]);
    onChange?.();
  }

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      const payload = buildPayload();
      if (!payload.promo_id) {
        alert("Vui long nhap ma khuyen mai.");
        return;
      }

      if (editingId) {
        await api(`/api/promos/${editingId}`, { method: "PUT", body: payload });
      } else {
        await api("/api/promos", { method: "POST", body: payload });
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
    if (!editingId) return;
    if (!confirm("Ban chac muon xoa khuyen mai nay?")) return;
    try {
      await api(`/api/promos/${editingId}`, { method: "DELETE" });
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
    const promoId = row?.dataset.id;
    if (!promoId) return;

    const promo = state.promos.find((p) => p.promo_id === promoId);
    if (!promo) return;

    const action = actionBtn.dataset.action;
    if (action === "edit") {
      fillForm(promo);
    }
    if (action === "delete") {
      if (!confirm("Ban chac muon xoa khuyen mai nay?")) return;
      try {
        await api(`/api/promos/${promoId}`, { method: "DELETE" });
        if (editingId === promoId) resetForm();
        await refreshAll();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  return { render, resetForm };
}
