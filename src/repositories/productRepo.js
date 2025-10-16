// src/repositories/productRepo.js
import { client as db } from "../config/cassandra.js";

export const ProductRepo = {
  async getAll() {
    try {
      const rs = await db.execute("SELECT * FROM products_by_id");
      return rs.rows ?? [];
    } catch (err) {
      console.error("❌ ProductRepo.getAll:", err.message);
      return [];
    }
  },

  async getByCategory(category) {
    try {
      const q = "SELECT * FROM products_by_category WHERE category = ?";
      const rs = await db.execute(q, [category], { prepare: true });
      return rs.rows ?? [];
    } catch (err) {
      console.error("❌ ProductRepo.getByCategory:", err.message);
      return [];
    }
  },

  async create(data) {
    try {
      const q = `
        INSERT INTO products_by_id (product_id, name, category, price, image_url, status)
        VALUES (?, ?, ?, ?, ?, ?)
      `;
      const params = [
        data.product_id,
        data.name,
        data.category,
        data.price || 0,
        data.image_url,
        data.status || "available",
      ];
      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("❌ ProductRepo.create:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async update(id, data) {
    try {
      const q = `
        UPDATE products_by_id SET 
          name=?, category=?, price=?, image_url=?, status=?
        WHERE product_id=?
      `;
      const params = [
        data.name,
        data.category,
        data.price || 0,
        data.image_url,
        data.status,
        id,
      ];
      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("❌ ProductRepo.update:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async delete(id) {
    try {
      await db.execute("DELETE FROM products_by_id WHERE product_id=?", [id], {
        prepare: true,
      });
      return { ok: true };
    } catch (err) {
      console.error("❌ ProductRepo.delete:", err.message);
      return { ok: false, error: err.message };
    }
  },
};
