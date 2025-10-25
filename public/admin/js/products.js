import { api } from './api.js';

const formatCurrency = (value) => {
  const number = Number(value ?? 0);
  return number.toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
};

export function initProducts({ state, loadProducts, loadPromoProducts, onChange }) {
  const form = document.getElementById('productForm');
  const tableBody = document.getElementById('productTableBody');
  const resetBtn = document.getElementById('productResetBtn');
  const deleteBtn = document.getElementById('productDeleteBtn');

  const idInput = document.getElementById('product_id');
  const nameInput = document.getElementById('product_name');
  const categoryInput = document.getElementById('product_category');
  const priceInput = document.getElementById('product_price');
  const imageInput = document.getElementById('product_image');
  const statusSelect = document.getElementById('product_status');

  let editingId = null;

  function buildPayload() {
    return {
      product_id: (idInput.value || '').trim(),
      name: (nameInput.value || '').trim(),
      category: (categoryInput.value || '').trim(),
      price: Number(priceInput.value || 0),
      image_url: (imageInput.value || '').trim(),
      status: statusSelect.value || 'active',
    };
  }

  function resetForm() {
    editingId = null;
    form.reset();
    idInput.removeAttribute('readonly');
    deleteBtn.disabled = true;
    statusSelect.value = 'active';
  }

  function fillForm(product) {
    editingId = product.product_id;
    idInput.value = product.product_id || '';
    idInput.setAttribute('readonly', 'readonly');
    nameInput.value = product.name || '';
    categoryInput.value = product.category || '';
    priceInput.value =
      product.price !== null && product.price !== undefined ? Number(product.price) : '';
    imageInput.value = product.image_url || '';
    statusSelect.value = product.status || 'active';
    deleteBtn.disabled = false;
  }

  function render() {
    if (!tableBody) return;
    if (!state.products.length) {
      tableBody.innerHTML =
        '<tr><td colspan="6" class="text-center muted">Chua có s?n ph?m</td></tr>';
      return;
    }

    const rows = state.products
      .slice()
      .sort((a, b) => a.product_id.localeCompare(b.product_id))
      .map((product) => {
        const status = (product.status || '').toLowerCase();
        const statusBadge =
          status === 'active'
            ? '<span class="badge success">Ho?t d?ng</span>'
            : '<span class="badge warning">T?m d?ng</span>';
        return 
          <tr data-id=''>
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

    tableBody.innerHTML = rows;
  }

  async function refreshAll() {
    await loadProducts();
    render();
    await loadPromoProducts();
    onChange?.();
  }

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    try {
      const payload = buildPayload();
      if (!payload.product_id) {
        alert('Vui ḷng nh?p mă s?n ph?m.');
        return;
      }
      if (!payload.name) {
        alert('Vui ḷng nh?p tên s?n ph?m.');
        return;
      }

      if (editingId) {
        await api(/api/products/, {
          method: 'PUT',
          body: payload,
        });
      } else {
        await api('/api/products', { method: 'POST', body: payload });
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
    if (!editingId) return;
    if (!confirm('B?n ch?c ch?n mu?n xóa s?n ph?m này?')) return;
    try {
      await api(/api/products/, { method: 'DELETE' });
      resetForm();
      await refreshAll();
    } catch (err) {
      alert(err.message);
    }
  });

  tableBody?.addEventListener('click', async (event) => {
    const actionBtn = event.target.closest('button[data-action]');
    if (!actionBtn) return;
    const row = actionBtn.closest('tr');
    const productId = row?.dataset.id;
    if (!productId) return;
    const product = state.products.find((p) => p.product_id === productId);
    if (!product) return;

    const action = actionBtn.dataset.action;
    if (action === 'edit') {
      fillForm(product);
    }
    if (action === 'delete') {
      if (!confirm('B?n ch?c ch?n mu?n xóa s?n ph?m này?')) return;
      try {
        await api(/api/products/, { method: 'DELETE' });
        if (editingId === productId) resetForm();
        await refreshAll();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  return { render, resetForm };
}
