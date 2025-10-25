import { loadLayout, setActiveNav } from "./layout.js";

const TOKEN_KEY = "promoshop_admin_token_v1";
let token = localStorage.getItem(TOKEN_KEY) || null;
let account = null;
let loginOverlay = null;
const loginWaiters = [];

function ensureLoginOverlay() {
  if (loginOverlay) return loginOverlay;
  loginOverlay = document.createElement("div");
  loginOverlay.className = "admin-login-overlay hidden";
  loginOverlay.innerHTML = `
    <div class="admin-login-card">
      <div>
        <h2>Đăng nhập quản trị</h2>
        <p>Chỉ quản trị viên được phép truy cập hệ thống này.</p>
      </div>
      <form id="admin-login-form" autocomplete="on">
        <div>
          <label for="admin-phone">Tên đăng nhập (SĐT)</label>
          <input id="admin-phone" name="phone" type="tel" placeholder="0900000000" required />
        </div>
        <div>
          <label for="admin-password">Mật khẩu</label>
          <input id="admin-password" name="password" type="password" placeholder="•••••••" required />
        </div>
        <button class="btn-primary" type="submit" id="admin-login-submit">Đăng nhập</button>
        <div class="muted" id="admin-login-error"></div>
      </form>
    </div>
  `;
  document.body.appendChild(loginOverlay);

  loginOverlay.querySelector("#admin-login-form").addEventListener("submit", async (evt) => {
    evt.preventDefault();
    const submitBtn = loginOverlay.querySelector("#admin-login-submit");
    const errorBox = loginOverlay.querySelector("#admin-login-error");
    submitBtn.disabled = true;
    errorBox.textContent = "";
    const payload = {
      phone: loginOverlay.querySelector("#admin-phone").value.trim(),
      password: loginOverlay.querySelector("#admin-password").value.trim(),
      remember: true,
    };
    try {
      await login(payload);
      hideLoginOverlay();
      resolveWaiters(account);
    } catch (err) {
      errorBox.textContent = err.message || "Không thể đăng nhập. Vui lòng thử lại.";
    } finally {
      submitBtn.disabled = false;
    }
  });

  return loginOverlay;
}

function showLoginOverlay(message) {
  const overlay = ensureLoginOverlay();
  const errorBox = overlay.querySelector("#admin-login-error");
  if (message) errorBox.textContent = message;
  overlay.classList.remove("hidden");
}

function hideLoginOverlay() {
  if (!loginOverlay) return;
  loginOverlay.classList.add("hidden");
  const errorBox = loginOverlay.querySelector("#admin-login-error");
  if (errorBox) errorBox.textContent = "";
}

function resolveWaiters(payload) {
  while (loginWaiters.length) {
    const waiter = loginWaiters.shift();
    if (typeof waiter === "function") waiter(payload);
  }
}

function storeToken(newToken) {
  token = newToken;
  if (token) {
    localStorage.setItem(TOKEN_KEY, token);
  } else {
    localStorage.removeItem(TOKEN_KEY);
  }
}

function setAccount(profile) {
  account = profile;
  updateHeader(profile);
}

function clearAuth() {
  storeToken(null);
  setAccount(null);
}

async function login(payload) {
  const res = await adminFetch("/api/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
    auth: "public",
  });
  if (!res?.token || !res?.account) {
    throw new Error("Thông tin đăng nhập không hợp lệ.");
  }
  if ((res.account.role || "").toLowerCase() !== "admin") {
    throw new Error("Tài khoản không có quyền quản trị.");
  }
  storeToken(res.token);
  setAccount(res.account);
  return res.account;
}

async function logout() {
  try {
    await adminFetch("/api/auth/logout", { method: "POST", auth: "silent" });
  } catch (_) {
    // ignore
  }
  clearAuth();
  showLoginOverlay("Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại.");
  return null;
}

async function fetchProfile() {
  if (!token) throw new Error("AUTH_MISSING");
  const res = await adminFetch("/api/auth/me", { auth: "silent" });
  if (!res?.account) throw new Error("AUTH_MISSING");
  if ((res.account.role || "").toLowerCase() !== "admin") {
    throw new Error("PERMISSION_DENIED");
  }
  setAccount(res.account);
  return res.account;
}

function updateHeader(profile) {
  const nameEl = document.getElementById("admin-user-name");
  const roleEl = document.getElementById("admin-user-role");
  const avatarEl = document.getElementById("admin-avatar");
  const wrapper = document.getElementById("admin-user-info");

  if (!wrapper) return;
  if (!profile) {
    wrapper.classList.add("hidden");
    return;
  }

  wrapper.classList.remove("hidden");
  if (nameEl) nameEl.textContent = profile.full_name || profile.phone || "Admin";
  if (roleEl) roleEl.textContent = "Quản trị viên";
  if (avatarEl) {
    const label = (profile.full_name || profile.phone || "AD")
      .split(" ")
      .map((part) => part[0])
      .join("")
      .slice(0, 2)
      .toUpperCase();
    avatarEl.textContent = label;
  }
}

async function ensureAuthenticated() {
  if (account) return account;
  if (token) {
    try {
      return await fetchProfile();
    } catch (err) {
      clearAuth();
      console.warn("Không thể lấy thông tin quản trị:", err.message);
    }
  }

  return new Promise((resolve) => {
    loginWaiters.push(resolve);
    showLoginOverlay("Vui lòng đăng nhập để tiếp tục.");
  });
}

export async function initAdmin({ activeNav } = {}) {
  await loadLayout();
  if (activeNav) setActiveNav(activeNav);

  const logoutBtn = document.getElementById("btn-admin-logout");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
      await logout();
    });
  }

  const profile = await ensureAuthenticated();
  setAccount(profile);
  return profile;
}

export async function adminFetch(path, options = {}) {
  const { auth = "auto", ...rest } = options;
  const headers = new Headers(rest.headers || {});

  if (rest.body && typeof rest.body !== "string" && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }

  if (token && auth !== "public") {
    headers.set("Authorization", `Bearer ${token}`);
  }

  const response = await fetch(path, { ...rest, headers });
  let payload = null;
  const contentType = response.headers.get("content-type") || "";

  if (contentType.includes("application/json")) {
    try {
      payload = await response.json();
    } catch (err) {
      console.warn("Không thể phân tích JSON:", err);
    }
  } else {
    payload = await response.text().catch(() => null);
  }

  if (!response.ok || (payload && payload.ok === false)) {
    const status = response.status;
    const message =
      (payload && (payload.error || payload.msg || payload.message)) ||
      `Yêu cầu thất bại (${status})`;

    if (status === 401 && auth !== "public") {
      clearAuth();
      showLoginOverlay("Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.");
      throw new Error("Vui lòng đăng nhập");
    }

    const error = new Error(message);
    error.status = status;
    throw error;
  }

  return payload;
}

export function getAdminAccount() {
  return account;
}

export { logout };
