// src/repositories/adminRepo.js
import { client as db } from "../config/cassandra.js";
import { types } from "cassandra-driver";

// Helper to convert ISO date strings into Cassandra LocalDate
function toLocalDate(str) {
  if (!str) return null;
  return types.LocalDate.fromDate(new Date(str));
}

export const AdminRepo = {
  // ====== PROMOS BY ID ======
  async getAllPromos() {
    try {
      const rs = await db.execute("SELECT * FROM promotions_by_id");
      return rs.rows ?? [];
    } catch (err) {
      console.error("[AdminRepo] getAllPromos failed:", err.message);
      return [];
    }
  },

  async getPromoById(promoId) {
    try {
      const q = "SELECT * FROM promotions_by_id WHERE promo_id = ?";
      const rs = await db.execute(q, [promoId], { prepare: true });
      return rs.rows[0] || null;
    } catch (err) {
      console.error("[AdminRepo] getPromoById failed:", err.message);
      return null;
    }
  },

  async createPromo(payload) {
    try {
      const {
        promo_id,
        name,
        type,
        start_date,
        end_date,
        description,
        stackable = false,
        min_order_amount = 0,
        limit_per_customer = 0,
        global_quota = null,
        channels = ["online"],
      } = payload;

      const q = `
        INSERT INTO promotions_by_id
        (promo_id, name, type, start_date, end_date, description,
         stackable, min_order_amount, limit_per_customer, global_quota, channels)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
      `;

      const params = [
        promo_id,
        name,
        type,
        toLocalDate(start_date),
        toLocalDate(end_date),
        description,
        !!stackable,
        types.BigDecimal.fromNumber(+min_order_amount || 0),
        parseInt(limit_per_customer || 0, 10),
        global_quota === null || global_quota === ""
          ? null
          : parseInt(global_quota, 10),
        Array.isArray(channels)
          ? channels
          : String(channels || "")
              .split(",")
              .map((s) => s.trim())
              .filter(Boolean),
      ];

      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[AdminRepo] createPromo failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async updatePromo(promoId, payload) {
    try {
      const {
        name,
        type,
        start_date,
        end_date,
        description,
        stackable,
        min_order_amount,
        limit_per_customer,
        global_quota,
        channels,
      } = payload;

      const q = `
        UPDATE promotions_by_id SET
          name=?, type=?, start_date=?, end_date=?, description=?, stackable=?,
          min_order_amount=?, limit_per_customer=?, global_quota=?, channels=?
        WHERE promo_id=?
      `;

      const params = [
        name,
        type,
        toLocalDate(start_date),
        toLocalDate(end_date),
        description,
        !!stackable,
        types.BigDecimal.fromNumber(+min_order_amount || 0),
        parseInt(limit_per_customer || 0, 10),
        global_quota === null || global_quota === ""
          ? null
          : parseInt(global_quota, 10),
        Array.isArray(channels)
          ? channels
          : String(channels || "")
              .split(",")
              .map((s) => s.trim())
              .filter(Boolean),
        promoId,
      ];

      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[AdminRepo] updatePromo failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async deletePromo(promoId) {
    try {
      await db.execute("DELETE FROM promotions_by_id WHERE promo_id=?", [promoId], {
        prepare: true,
      });
      return { ok: true };
    } catch (err) {
      console.error("[AdminRepo] deletePromo failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  // ====== PRODUCTS BY PROMO ======
  async listAllPromoProducts() {
    try {
      const rs = await db.execute("SELECT * FROM products_by_promo");
      return rs.rows ?? [];
    } catch (err) {
      console.error("[AdminRepo] listAllPromoProducts failed:", err.message);
      return [];
    }
  },

  async listProductsInPromo(promoId) {
    try {
      const q = "SELECT * FROM products_by_promo WHERE promo_id = ?";
      const rs = await db.execute(q, [promoId], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("[AdminRepo] listProductsInPromo failed:", err.message);
      return [];
    }
  },

  async addProductToPromo(payload) {
    try {
      const {
        promo_id,
        product_id,
        discount_percent = null,
        discount_amount = null,
        gift_product_id = null,
      } = payload;

      if (!promo_id || !product_id) {
        return { ok: false, error: "Missing promo_id or product_id" };
      }

      const q = `
        INSERT INTO products_by_promo
        (promo_id, product_id, discount_percent, discount_amount, gift_product_id)
        VALUES (?,?,?,?,?)
      `;

      const params = [
        promo_id,
        product_id,
        discount_percent === "" || discount_percent === null || discount_percent === undefined
          ? null
          : parseInt(discount_percent, 10),
        discount_amount === "" || discount_amount === null || discount_amount === undefined
          ? null
          : parseInt(discount_amount, 10),
        gift_product_id || null,
      ];

      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[AdminRepo] addProductToPromo failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async updateProductInPromo(promoId, productId, payload) {
    try {
      return await this.addProductToPromo({
        ...payload,
        promo_id: promoId,
        product_id: productId,
      });
    } catch (err) {
      console.error("[AdminRepo] updateProductInPromo failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async removeProductFromPromo(promoId, productId) {
    try {
      const q = "DELETE FROM products_by_promo WHERE promo_id=? AND product_id=?";
      await db.execute(q, [promoId, productId], { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[AdminRepo] removeProductFromPromo failed:", err.message);
      return { ok: false, error: err.message };
    }
  },
};
