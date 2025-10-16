// src/repositories/accountRepo.js
import { client as db } from "../config/cassandra.js";

export const AccountRepo = {
  async getAll() {
    try {
      const rs = await db.execute("SELECT * FROM accounts_by_phone");
      return rs.rows ?? [];
    } catch (err) {
      console.error("[AccountRepo] getAll failed:", err.message);
      return [];
    }
  },

  async create(payload) {
    try {
      const phone = (payload?.phone || "").trim();
      const fullName = (payload?.full_name || "").trim();
      if (!phone) throw new Error("Phone is required");

      const q =
        "INSERT INTO accounts_by_phone (phone, full_name) VALUES (?, ?)";
      await db.execute(q, [phone, fullName || null], { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[AccountRepo] create failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async update(phone, payload) {
    try {
      const targetPhone = (phone || "").trim();
      if (!targetPhone) throw new Error("Phone is required");

      const fullName = (payload?.full_name || "").trim();
      const q = "UPDATE accounts_by_phone SET full_name=? WHERE phone=?";

      await db.execute(q, [fullName || null, targetPhone], { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[AccountRepo] update failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async remove(phone) {
    try {
      const targetPhone = (phone || "").trim();
      if (!targetPhone) throw new Error("Phone is required");

      await db.execute(
        "DELETE FROM accounts_by_phone WHERE phone=?",
        [targetPhone],
        { prepare: true }
      );
      return { ok: true };
    } catch (err) {
      console.error("[AccountRepo] remove failed:", err.message);
      return { ok: false, error: err.message };
    }
  },
};
