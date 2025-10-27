import { api } from "./api.js";

export const state = {
  promos: [],
  products: [],
  promoProducts: [],
  codes: [],
  orders: [],
  accounts: [],
};

const safeArray = (value) => (Array.isArray(value) ? value : []);

const toNumber = (value) => {
  if (value === null || value === undefined) return null;
  if (typeof value === "number") return value;
  if (typeof value === "bigint") return Number(value);
  if (typeof value === "object" && typeof value.toNumber === "function") {
    return value.toNumber();
  }
  const parsed = Number(value);
  return Number.isNaN(parsed) ? null : parsed;
};

const toArray = (value) => {
  if (!value) return [];
  if (Array.isArray(value)) return value;
  if (value instanceof Set) return Array.from(value.values());
  if (typeof value.values === "function") {
    return Array.from(value.values());
  }
  if (typeof value.toArray === "function") {
    return value.toArray();
  }
  return [value].filter(Boolean);
};

const normalizePromo = (promo) => ({
  ...promo,
  min_order_amount: toNumber(promo?.min_order_amount) ?? 0,
  limit_per_customer: toNumber(promo?.limit_per_customer) ?? 0,
  global_quota: promo?.global_quota === null ? null : toNumber(promo?.global_quota),
  channels: toArray(promo?.channels),
  stackable: !!promo?.stackable,
});

const normalizeProduct = (product) => ({
  ...product,
  price: toNumber(product?.price) ?? 0,
});

const normalizePromoProduct = (record) => ({
  ...record,
  discount_percent:
    record?.discount_percent === null || record?.discount_percent === undefined
      ? null
      : toNumber(record.discount_percent),
  discount_amount:
    record?.discount_amount === null || record?.discount_amount === undefined
      ? null
      : toNumber(record.discount_amount),
});

const normalizeCode = (code) => ({
  ...code,
  enabled: !!code?.enabled,
});

const normalizeOrder = (order) => ({
  ...order,
  total_amount: toNumber(order?.total_amount) ?? 0,
  discount_amount: toNumber(order?.discount_amount) ?? 0,
  final_amount: toNumber(order?.final_amount) ?? 0,
  order_date: (() => {
    const raw = order?.order_date;
    if (!raw) return "";
    if (raw instanceof Date) return raw.toISOString().slice(0, 10);
    if (typeof raw.toISOString === "function") {
      try {
        return raw.toISOString().slice(0, 10);
      } catch (_) {
        // fall through
      }
    }
    if (typeof raw.toString === "function") return raw.toString();
    return String(raw);
  })(),
});

const normalizeAccount = (account) => ({
  ...account,
});

export async function loadPromos() {
  state.promos = safeArray(await api("/api/promos")).map(normalizePromo);
  return state.promos;
}

export async function loadProducts() {
  state.products = safeArray(await api("/api/products")).map(normalizeProduct);
  return state.products;
}

export async function loadPromoProducts(params = {}) {
  const query = params.promo_id ? `?promo_id=${encodeURIComponent(params.promo_id)}` : "";
  state.promoProducts = safeArray(
    await api(`/api/promo-products${query}`)
  ).map(normalizePromoProduct);
  return state.promoProducts;
}

export async function loadCodes() {
  state.codes = safeArray(await api("/api/codes")).map(normalizeCode);
  return state.codes;
}

export async function loadOrders(params = {}) {
  const query = new URLSearchParams();
  if (params.promo_id) query.set("promo_id", params.promo_id);
  if (params.customer_id) query.set("customer_id", params.customer_id);
  const suffix = query.toString() ? `?${query.toString()}` : "";
  state.orders = safeArray(await api(`/api/orders${suffix}`)).map(normalizeOrder);
  return state.orders;
}

export async function loadAccounts() {
  state.accounts = safeArray(await api("/api/accounts")).map(normalizeAccount);
  return state.accounts;
}

