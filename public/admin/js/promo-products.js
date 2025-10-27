import { api } from './api.js';

const formatCurrency = (value) => {
  if (value === null || value === undefined || value === '') return '-';
  return Number(value).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
};

export function initPromoProducts({ state, loadPromoProducts, onChange }) {
  const form = document.getElementById('promoProductForm');
  const tableBody = document.getElementById('promoProductTableBody');
  const resetBtn = document.getElementById('promoProductResetBtn');
  const deleteBtn = document.getElementById('promoProductDeleteBtn');
  const filterSelect = document.getElementById('promoProductFilter');

  const promoSelect = document.getElementById('promoProductPromoId');
  const productSelect = document.getElementById('promoProductProductId');
  const percentInput = document.getElementById('promoProductPercent');
  const amountInput = document.getElementById('promoProductAmount');
  const giftInput = document.getElementById('promoProductGift');

  let editingPair = null;
  let currentFilter = 'all';

  function resetForm() {
    editingPair = null;
    form.reset();
    deleteBtn.classList.add('hidden');
  }

  function fillForm(record) {
    editingPair = { promo_id: record.promo_id, product_id: record.product_id };
    promoSelect.value = record.promo_id || '';
    productSelect.value = record.product_id || '';
    percentInput.value =
      record.discount_percent !== null && record.discount_percent !== undefined
        ? Number(record.discount_percent)
        : '';
    amountInput.value =
      record.discount_amount !== null && record.discount_amount !== undefined
        ? Number(record.discount_amount)
        : '';
    giftInput.value = record.gift_product_id || '';
    deleteBtn.classList.remove('hidden');
  }

  function renderOptions() {
    if (promoSelect) {
      const current = promoSelect.value;
      promoSelect.innerHTML =
        '<option value="">Ch?n khuy?n mãi</option>' +
        state.promos
          .slice()
          .sort((a, b) => a.promo_id.localeCompare(b.promo_id))
          .map((promo) => {
            const note = promo.stackable ? '' : ' (không c?ng d?n)';
            const name = promo.name ?  -  : '';
            return <option value=''></option>;
          })
          .join('');
      if (current) promoSelect.value = current;
    }

    if (productSelect) {
      const current = productSelect.value;
      productSelect.innerHTML =
        '<option value="">Ch?n s?n ph?m</option>' +
        state.products
          .slice()
          .sort((a, b) => a.product_id.localeCompare(b.product_id))
          .map((product) => {
            const name = product.name ?  -  : '';
            return <option value=''></option>;
          })
          .join('');
      if (current) productSelect.value = current;
    }

    if (filterSelect) {
      const current = filterSelect.value || 'all';
      filterSelect.innerHTML =
        '<option value="all">T?t c? chuong trình</option>' +
        state.promos
          .slice()
          .sort((a, b) => a.promo_id.localeCompare(b.promo_id))
          .map((promo) => <option value=''> - </option>)
          .join('');
      filterSelect.value =
        state.promos.some((p) => p.promo_id === current) || current === 'all' ? current : 'all';
      currentFilter = filterSelect.value;
    }
  }

  function renderTable() {
    if (!tableBody) return;
    if (!state.promoProducts.length) {
      tableBody.innerHTML =
        '<tr><td colspan="6" class="text-center muted">Chua có s?n ph?m áp d?ng</td></tr>';
      return;
    }

    const rows = state.promoProducts
      .filter((record) => currentFilter === 'all' || record.promo_id === currentFilter)
      .slice()
      .sort((a, b) => {
        const cmp = a.promo_id.localeCompare(b.promo_id);
        return cmp !== 0 ? cmp : a.product_id.localeCompare(b.product_id);
      })
      .map((record) => {
        const promo = state.promos.find((p) => p.promo_id === record.promo_id);
        const product = state.products.find((p) => p.product_id === record.product_id);
        const promoLabel = promo ? ${promo.promo_id} -  : record.promo_id;
        const productLabel = product ? ${product.product_id} -  : record.product_id;
        const stackableBadge = promo && !promo.stackable
          ? '<span class="badge warning" style="margin-left:6px;">Không c?ng d?n</span>'
          : '';

        return 
          <tr data-promo='' data-product=''>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td class='text-end'>
              <button class='btn-outline btn-small' data-action='edit'>S?a</button>
              <button class='btn-danger btn-small' data-action='delete' style='margin-left:6px'>Xóa</button>
            </td>
          </tr>
        ;
      })
      .join('');

    tableBody.innerHTML =
      rows || '<tr><td colspan="6" class="text-center muted">Không có d? li?u</td></tr>';
  }

  function render() {
    renderOptions();
    renderTable();
  }

  async function refreshAll() {
    await loadPromoProducts();
    renderTable();
    onChange?.();
  }

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const promoId = promoSelect.value;
      const productId = productSelect.value;
      if (!promoId || !productId) {
        alert('Vui lòng ch?n khuy?n mãi và s?n ph?m.');
        return;
      }

      const percent = percentInput.value === '' ? null : Number(percentInput.value);
      const amount = amountInput.value === '' ? null : Number(amountInput.value);
      const gift = (giftInput.value || '').trim() || null;

      const payload = {
        promo_id: promoId,
        product_id: productId,
        discount_percent: percent,
        discount_amount: amount,
        gift_product_id: gift,
      };

      if (editingPair) {
        await api(/api/promo-products//,
          { method: 'PUT', body: payload });
      } else {
        await api('/api/promo-products', { method: 'POST', body: payload });
      }

      resetForm();
      await refreshAll();
    } catch (err) {
      alert(err.message);
    }
  });

  resetBtn?.addEventListener('click', () => {
    resetForm();
  });

  deleteBtn?.addEventListener('click', async () => {
    if (!editingPair) return;
    if (!confirm('B?n ch?c ch?n mu?n xóa áp d?ng này?')) return;
    try {
      await api(/api/promo-products//, {
        method: 'DELETE',
      });
      resetForm();
      await refreshAll();
    } catch (err) {
      alert(err.message);
    }
  });

  filterSelect?.addEventListener('change', () => {
    currentFilter = filterSelect.value || 'all';
    renderTable();
  });

  tableBody?.addEventListener('click', async (event) => {
    const actionBtn = event.target.closest('button[data-action]');
    if (!actionBtn) return;
    const row = actionBtn.closest('tr');
    const promoId = row?.dataset.promo;
    const productId = row?.dataset.product;
    if (!promoId || !productId) return;

    const record = state.promoProducts.find(
      (item) => item.promo_id === promoId && item.product_id === productId
    );
    if (!record) return;

    const action = actionBtn.dataset.action;
    if (action === 'edit') {
      fillForm(record);
    }
    if (action === 'delete') {
      if (!confirm('B?n ch?c ch?n mu?n xóa áp d?ng này?')) return;
      try {
        await api(/api/promo-products//, { method: 'DELETE' });
        if (editingPair && editingPair.promo_id === promoId && editingPair.product_id === productId) {
          resetForm();
        }
        await refreshAll();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  return { render, resetForm };
}
