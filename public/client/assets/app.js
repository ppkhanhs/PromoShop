const STORAGE_TOKEN = "promoshop_token_v1";
const STORAGE_ACCOUNT = "promoshop_account_v1";

const eventBus = new EventTarget();
const state = {
  token: localStorage.getItem(STORAGE_TOKEN),
  account: null,
  activeNav: null,
  headerRendered: false,
  modalType: null,
  modalEl: null,
  cartCount: 0,
};

const formatCurrency = (value) =>
  new Intl.NumberFormat("vi-VN").format(Number(value || 0)) + " đ";

function ensureToastContainer() {
  let toast = document.querySelector(".toast-container");
  if (!toast) {
    toast = document.createElement("div");
    toast.className = "toast-container";
    document.body.appendChild(toast);
  }
  return toast;
}

function showToast(message, type = "success") {
  const container = ensureToastContainer();
  const div = document.createElement("div");
  div.className = `toast ${type}`;
  div.innerHTML = `<strong>${message}</strong>`;
  container.appendChild(div);
  setTimeout(() => {
    div.style.opacity = "0";
    setTimeout(() => div.remove(), 260);
  }, 3000);
}

function setAuth(token, account) {
  state.token = token;
  state.account = account;
  if (token) {
    localStorage.setItem(STORAGE_TOKEN, token);
  } else {
    localStorage.removeItem(STORAGE_TOKEN);
  }
  if (account) {
    localStorage.setItem(STORAGE_ACCOUNT, JSON.stringify(account));
  } else {
    localStorage.removeItem(STORAGE_ACCOUNT);
  }
  eventBus.dispatchEvent(
    new CustomEvent("auth:changed", { detail: { account: state.account } })
  );
  updateHeaderUI();
}

function clearAuth() {
  setAuth(null, null);
}

function renderHeaderShell() {
  const header =
    document.getElementById("site-header") ||
    (() => {
      const el = document.createElement("header");
      el.id = "site-header";
      el.className = "site-header";
      document.body.prepend(el);
      return el;
    })();

  header.innerHTML = `
    <div class="site-header__inner">
      <a class="site-logo" href="/">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
          <path d="M4 7.5C4 5.567 5.567 4 7.5 4h9c1.933 0 3.5 1.567 3.5 3.5v9c0 1.933-1.567 3.5-3.5 3.5h-9C5.567 20 4 18.433 4 16.5v-9Z" stroke="#1877f2" stroke-width="1.6"/>
          <path d="M8 12h8M8 15h5" stroke="#1877f2" stroke-width="1.6" stroke-linecap="round"/>
          <path d="M12 8.5c.83 0 1.5-.67 1.5-1.5S12.83 5.5 12 5.5 10.5 6.17 10.5 7s.67 1.5 1.5 1.5Z" fill="#1877f2"/>
        </svg>
        Promo<span>Shop</span>
      </a>
      <nav class="site-nav" id="site-nav">
        <a data-nav="home" href="/">Trang chủ</a>
        <a data-nav="cart" href="/cart">
          Giỏ hàng
          <span class="badge-pill" id="nav-cart-count">0</span>
        </a>
        <a data-nav="orders" href="/orders">Đơn hàng</a>
      </nav>
      <div class="site-actions" id="site-actions"></div>
    </div>
  `;

  const navLinks = header.querySelectorAll(".site-nav a");
  navLinks.forEach((link) => {
    if (link.dataset.nav === state.activeNav) {
      link.classList.add("is-active");
    } else {
      link.classList.remove("is-active");
    }
  });

  bindHeaderActions();
}

function bindHeaderActions() {
  const actions = document.getElementById("site-actions");
  if (!actions) return;
  actions.innerHTML = "";

  if (!state.account || !state.token) {
    const loginBtn = document.createElement("button");
    loginBtn.className = "btn-primary";
    loginBtn.textContent = "Đăng nhập";
    loginBtn.addEventListener("click", () => openAuthModal("login"));

    const registerBtn = document.createElement("button");
    registerBtn.className = "btn-outline";
    registerBtn.textContent = "Đăng ký";
    registerBtn.addEventListener("click", () => openAuthModal("register"));

    actions.appendChild(loginBtn);
    actions.appendChild(registerBtn);
  } else {
    const chip = document.createElement("div");
    chip.className = "user-chip";
    chip.innerHTML = `
      <div class="user-avatar">${getInitials(state.account.full_name)}</div>
      <div>
        <div style="font-size:13px;font-weight:600">${state.account.full_name || "Thành viên"}</div>
        <div style="font-size:12px" class="muted">${state.account.phone}</div>
      </div>
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
        <path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
      <div class="dropdown" id="user-dropdown">
        <a href="/orders">Đơn hàng của tôi</a>
        <button type="button" data-action="logout">Đăng xuất</button>
      </div>
    `;
    const dropdown = chip.querySelector("#user-dropdown");
    chip.addEventListener("click", (evt) => {
      evt.stopPropagation();
      const isOpen = dropdown.style.display === "block";
      dropdown.style.display = isOpen ? "none" : "block";
    });
    document.addEventListener("click", () => {
      dropdown.style.display = "none";
    });
    dropdown
      .querySelector('button[data-action="logout"]')
      .addEventListener("click", async (evt) => {
        evt.preventDefault();
        evt.stopPropagation();
        await App.auth.logout();
      });
    actions.appendChild(chip);
  }
}

function getInitials(name) {
  if (!name) return "P";
  return name
    .split(" ")
    .map((w) => w.charAt(0))
    .join("")
    .slice(0, 2)
    .toUpperCase();
}

function updateHeaderUI() {
  renderHeaderShell();
  const badge = document.getElementById("nav-cart-count");
  if (badge) {
    badge.textContent = state.cartCount;
  }
}

function ensureModal() {
  if (state.modalEl) return state.modalEl;
  const backdrop = document.createElement("div");
  backdrop.className = "modal-backdrop";
  backdrop.id = "auth-modal";
  backdrop.innerHTML = `
    <div class="modal" role="dialog" aria-modal="true">
      <button class="modal-close" aria-label="Đóng">&times;</button>
      <div class="modal-body"></div>
    </div>
  `;
  document.body.appendChild(backdrop);
  backdrop.addEventListener("click", (evt) => {
    if (evt.target === backdrop) closeAuthModal();
  });
  backdrop.querySelector(".modal-close").addEventListener("click", closeAuthModal);
  state.modalEl = backdrop;
  return backdrop;
}

function openAuthModal(type = "login") {
  const modal = ensureModal();
  state.modalType = type;
  const body = modal.querySelector(".modal-body");
  if (type === "login") {
    body.innerHTML = `
      <h3>Chào mừng trở lại</h3>
      <p class="muted">Đăng nhập để tiếp tục trải nghiệm ưu đãi dành riêng cho bạn.</p>
      <form id="form-login" class="form-grid">
        <div>
          <label for="login-phone">Số điện thoại</label>
          <input id="login-phone" name="phone" type="tel" placeholder="0901234567" required />
        </div>
        <div>
          <label for="login-password">Mật khẩu</label>
          <input id="login-password" name="password" type="password" placeholder="*******" required />
        </div>
        <button class="btn-primary" type="submit">Đăng nhập</button>
      </form>
      <div class="auth-switch">
        Chưa có tài khoản?
        <button type="button" data-switch="register">Tạo tài khoản</button>
      </div>
    `;
    body
      .querySelector("#form-login")
      .addEventListener("submit", async (evt) => {
        evt.preventDefault();
        const form = evt.currentTarget;
        const payload = {
          phone: form.phone.value.trim(),
          password: form.password.value.trim(),
          remember: true,
        };
        try {
          await App.auth.login(payload);
          closeAuthModal();
          showToast("Đăng nhập thành công", "success");
        } catch (err) {
          showToast(err.message || "Đăng nhập thất bại", "error");
        }
      });
  } else {
    body.innerHTML = `
      <h3>Tạo tài khoản mới</h3>
      <p class="muted">Chỉ mất vài giây để bắt đầu săn ưu đãi và lưu lịch sử đơn hàng.</p>
      <form id="form-register" class="form-grid">
        <div>
          <label for="reg-name">Họ tên</label>
          <input id="reg-name" name="full_name" type="text" placeholder="Nguyễn Văn A" required />
        </div>
        <div>
          <label for="reg-phone">Số điện thoại</label>
          <input id="reg-phone" name="phone" type="tel" placeholder="0987654321" required />
        </div>
        <div>
          <label for="reg-email">Email (không bắt buộc)</label>
          <input id="reg-email" name="email" type="email" placeholder="ban@example.com" />
        </div>
        <div>
          <label for="reg-password">Mật khẩu</label>
          <input id="reg-password" name="password" type="password" placeholder="Tối thiểu 6 ký tự" required />
        </div>
        <button class="btn-primary" type="submit">Đăng ký</button>
      </form>
      <div class="auth-switch">
        Đã có tài khoản?
        <button type="button" data-switch="login">Đăng nhập</button>
      </div>
    `;
    body
      .querySelector("#form-register")
      .addEventListener("submit", async (evt) => {
        evt.preventDefault();
        const form = evt.currentTarget;
        const payload = {
          full_name: form.full_name.value.trim(),
          phone: form.phone.value.trim(),
          email: form.email.value.trim(),
          password: form.password.value.trim(),
          remember: true,
        };
        try {
          await App.auth.register(payload);
          closeAuthModal();
          showToast("Đăng ký thành công, bắt đầu mua sắm thôi!", "success");
        } catch (err) {
          showToast(err.message || "Đăng ký thất bại", "error");
        }
      });
  }
  body
    .querySelector(".auth-switch button")
    .addEventListener("click", (evt) => {
      const target = evt.currentTarget.getAttribute("data-switch");
      openAuthModal(target);
    });
  modal.style.display = "flex";
}

function closeAuthModal() {
  if (!state.modalEl) return;
  state.modalEl.style.display = "none";
  state.modalType = null;
}

async function apiFetch(url, options = {}) {
  const { auth = "auto", ...rest } = options;
  const headers = new Headers(rest.headers || {});
  if (rest.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  if (state.token && auth !== "public") {
    headers.set("Authorization", `Bearer ${state.token}`);
  }

  const response = await fetch(url, { ...rest, headers });
  let payload = null;
  try {
    payload = await response.json();
  } catch {
    // ignore
  }

  const requestFailed =
    !response.ok || (payload && payload.ok === false);

  if (requestFailed) {
    const message =
      payload?.error ||
      payload?.msg ||
      (response.status === 404 ? "Không tìm thấy dữ liệu" : "Yêu cầu không thành công");

    if (response.status === 401 && auth !== "public") {
      clearAuth();
      if (auth !== "silent") {
        openAuthModal("login");
      }
      const err = new Error("Vui lòng đăng nhập");
      err.code = "AUTH_REQUIRED";
      throw err;
    }

    const err = new Error(message);
    err.code = response.status || "REQUEST_FAILED";
    throw err;
  }

  return payload ?? {};
}

async function bootstrapAccount() {
  if (!state.token) {
    const cached = localStorage.getItem(STORAGE_ACCOUNT);
    if (cached) {
      try {
        state.account = JSON.parse(cached);
      } catch {
        state.account = null;
      }
    }
    updateHeaderUI();
    return;
  }
  try {
    const res = await apiFetch("/api/auth/me", { auth: "silent" });
    if (res?.account) {
      setAuth(state.token, res.account);
    } else {
      clearAuth();
    }
  } catch {
    clearAuth();
  }
}

async function refreshCartBadge() {
  if (!state.token) {
    state.cartCount = 0;
    updateHeaderUI();
    return;
  }
  try {
    const res = await apiFetch("/api/customer/cart", { method: "GET" });
    state.cartCount = res?.totals?.count || 0;
    updateHeaderUI();
  } catch (err) {
    if (err.code !== "AUTH_REQUIRED") {
      console.warn("Không thể cập nhật giỏ hàng:", err.message);
    }
  }
}

const App = {
  init: async (activeNav = null) => {
    state.activeNav = activeNav;
    renderHeaderShell();
    ensureModal();
    await bootstrapAccount();
    if (state.account) {
      await refreshCartBadge();
    }
  },
  apiFetch,
  formatCurrency,
  toast: showToast,
  auth: {
    isAuthenticated: () => Boolean(state.token && state.account),
    account: () => state.account,
    ensure: async () => {
      if (state.token && state.account) return state.account;
      openAuthModal("login");
      const err = new Error("Vui lòng đăng nhập");
      err.code = "AUTH_REQUIRED";
      throw err;
    },
    onChange: (handler) => {
      eventBus.addEventListener("auth:changed", handler);
    },
    login: async (payload) => {
      const res = await apiFetch("/api/auth/login", {
        method: "POST",
        body: JSON.stringify(payload),
        auth: "public",
      });
      if (res?.token && res?.account) {
        setAuth(res.token, res.account);
        await refreshCartBadge();
      }
      return res;
    },
    register: async (payload) => {
      const res = await apiFetch("/api/auth/register", {
        method: "POST",
        body: JSON.stringify(payload),
        auth: "public",
      });
      if (res?.token && res?.account) {
        setAuth(res.token, res.account);
        await refreshCartBadge();
      }
      return res;
    },
    logout: async () => {
      try {
        await apiFetch("/api/auth/logout", { method: "POST", auth: "silent" });
      } catch {
        // ignore
      } finally {
        clearAuth();
        showToast("Bạn đã đăng xuất", "success");
        state.cartCount = 0;
        updateHeaderUI();
      }
    },
  },
  cart: {
    refresh: refreshCartBadge,
    setCount: (count) => {
      state.cartCount = count;
      updateHeaderUI();
    },
  },
  modals: {
    login: () => openAuthModal("login"),
    register: () => openAuthModal("register"),
    close: closeAuthModal,
  },
};

window.App = App;
export { App, formatCurrency };
