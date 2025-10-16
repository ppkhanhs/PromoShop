import { api } from "./api.js";

export function initProducts({ state, loadProducts, loadPromoProducts, onChange }) {
  const form = document.getElementById("productForm");
  const tableBody = document.getElementById("productTableBody");
  const resetBtn = document.getElementById("productResetBtn");
  const deleteBtn = document.getElementById("productDeleteBtn");

  const idInput = document.getElementById("product_id");
  const nameInput = document.getElementById("product_name");
  const categoryInput = document.getElementById("product_category");
  const priceInput = document.getElementById("product_price");
  const imageInput = document.getElementById("product_image");
  const statusSelect = document.getElementById("product_status");

  let editingId = null;

  function buildPayload() {
    return {
      product_id: (idInput.value || "").trim(),
      name: (nameInput.value || "").trim(),
      category: (categoryInput.value || "").trim(),
      price: Number(priceInput.value || 0),
      image_url: (imageInput.value || "").trim(),
      status: statusSelect.value || "active",
    };
  }

  function resetForm() {
    editingId = null;
    form.reset();
    idInput.removeAttribute("readonly");
    deleteBtn.disabled = true;
    statusSelect.value = "active";
  }

  function fillForm(product) {
    editingId = product.product_id;
    idInput.value = product.product_id || "";
    idInput.setAttribute("readonly", "readonly");
    nameInput.value = product.name || "";
    categoryInput.value = product.category || "";
    priceInput.value =
      product.price !== null && product.price !== undefined
        ? Number(product.price)
        : "";
    imageInput.value = product.image_url || "";
    statusSelect.value = product.status || "active";
    deleteBtn.disabled = false;
  }

  function render() {
    if (!tableBody) return;
    if (!state.products.length) {
      tableBody.innerHTML =
        '<tr><td colspan="6" class="text-center text-muted">Chưa có sản phẩm</td></tr>';
      return;
    }

    const rows = state.products
      .slice()
      .sort((a, b) => a.product_id.localeCompare(b.product_id))
      .map((product) => {
        const statusBadge =
          (product.status || "").toLowerCase() === "active"
            ? '<span class="badge success">Hoạt động</span>'
            : '<span class="badge warning">Tạm dừng</span>';
        return `
          <tr data-id="${product.product_id}">
            <td>${product.product_id}</td>
            <td>${product.name || ""}</td>
            <td>${product.category || ""}</td>
            <td>${product.price ?? ""}</td>
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

  async function refreshAll() {
    await loadProducts();
    render();
    await loadPromoProducts();
    onChange?.();
  }

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    try {
      const payload = buildPayload();
      if (!payload.product_id) {
        alert("Vui lòng nhập mã sản phẩm.");
        return;
      }
      if (!payload.name) {
        alert("Vui lòng nhập tên sản phẩm.");
        return;
      }

      if (editingId) {
        await api(`/api/products/${editingId}`, {
          method: "PUT",
          body: payload,
        });
      } else {
        await api("/api/products", { method: "POST", body: payload });
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
    if (!confirm("Bạn có chắc muốn xóa sản phẩm này?")) return;
    try {
      await api(`/api/products/${editingId}`, { method: "DELETE" });
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
    const productId = row?.dataset.id;
    if (!productId) return;
    const product = state.products.find((p) => p.product_id === productId);
    if (!product) return;

    const action = actionBtn.dataset.action;
    if (action === "edit") {
      fillForm(product);
    }
    if (action === "delete") {
      if (!confirm("Bạn có chắc muốn xóa sản phẩm này?")) return;
      try {
        await api(`/api/products/${productId}`, { method: "DELETE" });
        if (editingId === productId) resetForm();
        await refreshAll();
      } catch (err) {
        alert(err.message);
      }
    }
  });

  return { render, resetForm };
}
