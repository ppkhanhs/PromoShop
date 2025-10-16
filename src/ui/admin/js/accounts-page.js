import { loadLayout } from "./layout.js";
import { state, loadAccounts } from "./state.js";
import { api } from "./api.js";

document.addEventListener("DOMContentLoaded", async () => {
  await loadLayout();

  const form = document.getElementById("accountForm");
  const phoneInput = document.getElementById("account_phone");
  const nameInput = document.getElementById("account_name");
  const resetBtn = document.getElementById("accountResetBtn");
  const deleteBtn = document.getElementById("accountDeleteBtn");
  const tableBody = document.getElementById("accountsTableBody");
  const searchInput = document.getElementById("accountSearchInput");
  const searchBtn = document.getElementById("accountSearchBtn");
  const clearSearchBtn = document.getElementById("accountSearchClearBtn");

  let editingPhone = null;
  let keyword = "";

  const renderTable = () => {
    if (!tableBody) return;
    const filter = keyword.trim().toLowerCase();

    const rows = state.accounts
      .filter((item) =>
        filter
          ? item.phone?.toLowerCase().includes(filter) ||
            item.full_name?.toLowerCase().includes(filter)
          : true
      )
      .slice()
      .sort((a, b) => a.phone.localeCompare(b.phone))
      .map((item) => `
        <tr data-phone="${item.phone}">
          <td>${item.phone}</td>
          <td>${item.full_name || ""}</td>
          <td class="text-end">
            <button class="btn btn-sm btn-warning me-2" data-action="edit">Sua</button>
            <button class="btn btn-sm btn-danger" data-action="delete">Xoa</button>
          </td>
        </tr>
      `)
      .join("");

    tableBody.innerHTML =
      rows || '<tr><td colspan="3" class="text-center text-muted">Chua co tai khoan</td></tr>';
  };

  const resetForm = () => {
    editingPhone = null;
    form?.reset();
    phoneInput?.removeAttribute("readonly");
    deleteBtn?.classList.add("d-none");
  };

  const fillForm = (account) => {
    editingPhone = account.phone;
    phoneInput.value = account.phone || "";
    phoneInput.setAttribute("readonly", "readonly");
    nameInput.value = account.full_name || "";
    deleteBtn.classList.remove("d-none");
  };

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      const phone = (phoneInput.value || "").trim();
      const fullName = (nameInput.value || "").trim();
      if (!phone) {
        alert("Vui long nhap so dien thoai.");
        return;
      }

      const payload = { phone, full_name: fullName };

      if (editingPhone) {
        await api(`/api/accounts/${editingPhone}`, {
          method: "PUT",
          body: payload,
        });
      } else {
        await api("/api/accounts", { method: "POST", body: payload });
      }

      resetForm();
      await loadAccounts();
      renderTable();
    } catch (err) {
      alert(err.message);
    }
  });

  resetBtn?.addEventListener("click", (event) => {
    event.preventDefault();
    resetForm();
  });

  deleteBtn?.addEventListener("click", async (event) => {
    event.preventDefault();
    if (!editingPhone) return;
    if (!confirm("Ban chac muon xoa tai khoan nay?")) return;
    try {
      await api(`/api/accounts/${editingPhone}`, { method: "DELETE" });
      resetForm();
      await loadAccounts();
      renderTable();
    } catch (err) {
      alert(err.message);
    }
  });

  tableBody?.addEventListener("click", async (event) => {
    const button = event.target.closest("button[data-action]");
    if (!button) return;
    const row = button.closest("tr");
    const phone = row?.dataset.phone;
    if (!phone) return;
    const account = state.accounts.find((item) => item.phone === phone);
    if (!account) return;

    const action = button.dataset.action;
    if (action === "edit") {
      fillForm(account);
    }
    if (action === "delete") {
      if (!confirm("Ban chac muon xoa tai khoan nay?")) return;
      try {
        await api(`/api/accounts/${phone}`, { method: "DELETE" });
        if (editingPhone === phone) resetForm();
        await loadAccounts();
        renderTable();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  const applySearch = () => {
    keyword = (searchInput?.value || "").trim();
    renderTable();
  };

  searchBtn?.addEventListener("click", (event) => {
    event.preventDefault();
    applySearch();
  });

  clearSearchBtn?.addEventListener("click", (event) => {
    event.preventDefault();
    if (searchInput) searchInput.value = "";
    keyword = "";
    renderTable();
  });

  await loadAccounts();
  renderTable();
});
