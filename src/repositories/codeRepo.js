import { client as db } from "../config/cassandra.js";
import { types } from "cassandra-driver";

function toLocalDate(str) {
  if (!str) return null;
  return types.LocalDate.fromDate(new Date(str));
}

export const CodeRepo = {
  async getAllCodes() {
    try {
      const rs = await db.execute("SELECT * FROM promo_codes_by_code");
      return rs.rows ?? [];
    } catch (err) {
      console.error("[CodeRepo] getAllCodes failed:", err.message);
      return [];
    }
  },

  async create(data) {
    try {
      const q = `
        INSERT INTO promo_codes_by_code (code, promo_id, enabled, expire_date)
        VALUES (?, ?, ?, ?)
      `;
      const params = [
        data.code,
        data.promo_id,
        data.enabled ?? true,
        toLocalDate(data.expire_date),
      ];
      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[CodeRepo] create failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async update(code, data) {
    try {
      const q = `
        UPDATE promo_codes_by_code
        SET promo_id = ?, enabled = ?, expire_date = ?
        WHERE code = ?
      `;
      const params = [
        data.promo_id,
        data.enabled ?? true,
        toLocalDate(data.expire_date),
        code,
      ];
      await db.execute(q, params, { prepare: true });
      return { ok: true };
    } catch (err) {
      console.error("[CodeRepo] update failed:", err.message);
      return { ok: false, error: err.message };
    }
  },

  async delete(code) {
    try {
      await db.execute("DELETE FROM promo_codes_by_code WHERE code = ?", [code], {
        prepare: true,
      });
      return { ok: true };
    } catch (err) {
      console.error("[CodeRepo] delete failed:", err.message);
      return { ok: false, error: err.message };
    }
  },
};
