// src/repositories/promoRepo.js
import { client as db } from "../config/cassandra.js";

export const PromoRepo = {
  async getActivePromosByDay(day) {
    try {
      // Dùng câu query prepare thay vì nội suy chuỗi
      const q = `SELECT * FROM promotions_active_by_day WHERE day = ? ALLOW FILTERING`;
      const rs = await db.execute(q, [day], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("❌ PromoRepo.getActivePromosByDay:", err.message);
      return [];
    }
  },

  async getPromosByType(type) {
    try {
      const q = `SELECT * FROM promotions_by_type WHERE type = ? ALLOW FILTERING`;
      const rs = await db.execute(q, [type], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("❌ PromoRepo.getPromosByType:", err.message);
      return [];
    }
  },

  async getProductsInPromo(promoId) {
    try {
      const q = `SELECT * FROM products_by_promo WHERE promo_id = ?`;
      const rs = await db.execute(q, [promoId], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("❌ PromoRepo.getProductsInPromo:", err.message);
      return [];
    }
  },
};
