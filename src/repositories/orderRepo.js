// src/repositories/orderRepo.js
import { client as db } from "../config/cassandra.js";

export const OrderRepo = {
  async getAll() {
    try {
      const rs = await db.execute("SELECT * FROM orders_by_id");
      return rs.rows ?? [];
    } catch (err) {
      console.error("[OrderRepo] getAll failed:", err.message);
      return [];
    }
  },

  async getByPromo(promoId) {
    try {
      const q =
        "SELECT * FROM orders_by_promo_date WHERE promo_id = ? ALLOW FILTERING";
      const rs = await db.execute(q, [promoId], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("[OrderRepo] getByPromo failed:", err.message);
      return [];
    }
  },

  async getByCustomer(customerId) {
    try {
      const q =
        "SELECT * FROM orders_by_customer_date WHERE customer_id = ? ALLOW FILTERING";
      const rs = await db.execute(q, [customerId], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("[OrderRepo] getByCustomer failed:", err.message);
      return [];
    }
  },
};
