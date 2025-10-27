import { initAdmin } from './app.js';
import { state, loadAccounts } from './state.js';
import { api } from './api.js';

document.addEventListener('DOMContentLoaded', async () => {
  await initAdmin({ activeNav: 'accounts' });

  const form = document.getElementById('accountForm');
  const phoneInput = document.getElementById('account_phone');
  const nameInput = document.getElementById('account_name');
  const resetBtn = document.getElementById('accountResetBtn');
  const deleteBtn = document.getElementById('accountDeleteBtn');
  const tableBody = document.getElementById('accountsTableBody');
  const searchInput = document.getElementById('accountSearchInput');
  const searchBtn = document.getElementById('accountSearchBtn');
  const clearSearchBtn = document.getElementById('accountSearchClearBtn');

  let editingPhone = null;
  let keyword = '';

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
      .map((item) => 
        <tr data-phone="">
          <td></td>
          <td></td>
          <td class="text-end">
            <button class="btn-outline btn-small" data-action="edit">S?a</button>
            <button class="btn-danger btn-small" data-action="delete" style="margin-left:6px">Xóa</button>
          </td>
        </tr>
      )
      .join('');

    tableBody.innerHTML =
      rows || '<tr><td colspan="3" class="text-center muted">Chua có tài kho?n nào</td></tr>';
  };

  const resetForm = () => {
    editingPhone = null;
    form?.reset();
    phoneInput?.removeAttribute('readonly');
    deleteBtn?.classList.add('hidden');
  };

  const fillForm = (account) => {
    editingPhone = account.phone;
    phoneInput.value = account.phone || '';
    phoneInput.setAttribute('readonly', 'readonly');
    nameInput.value = account.full_name || '';
    deleteBtn.classList.remove('hidden');
  };

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const phone = (phoneInput.value || '').trim();
      const fullName = (nameInput.value || '').trim();
      if (!phone) {
        alert('Vui ḷng nh?p s? di?n tho?i.');
        return;
      }

      const payload = { phone, full_name: fullName };

      if (editingPhone) {
        await api(/api/accounts/, {
          method: 'PUT',
          body: payload,
        });
      } else {
        await api('/api/accounts', { method: 'POST', body: payload });
      }

      resetForm();
      await loadAccounts();
      renderTable();
    } catch (err) {
      alert(err.message);
    }
  });

  resetBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    resetForm();
  });

  deleteBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    if (!editingPhone) return;
    if (!confirm('B?n ch?c ch?n mu?n xóa tài kho?n này?')) return;
    try {
      await api(/api/accounts/, { method: 'DELETE' });
      resetForm();
      await loadAccounts();
      renderTable();
    } catch (err) {
      alert(err.message);
    }
  });

  tableBody?.addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const row = button.closest('tr');
    const phone = row?.dataset.phone;
    if (!phone) return;
    const account = state.accounts.find((item) => item.phone === phone);
    if (!account) return;

    const action = button.dataset.action;
    if (action === 'edit') {
      fillForm(account);
    }
    if (action === 'delete') {
      if (!confirm('B?n ch?c ch?n mu?n xóa tài kho?n này?')) return;
      try {
        await api(/api/accounts/, { method: 'DELETE' });
        if (editingPhone === phone) resetForm();
        await loadAccounts();
        renderTable();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  const applySearch = () => {
    keyword = (searchInput?.value || '').trim();
    renderTable();
  };

  searchBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    applySearch();
  });

  clearSearchBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    if (searchInput) searchInput.value = '';
    keyword = '';
    renderTable();
  });

  await loadAccounts();
  renderTable();
});
