import express from "express";
import { client } from "../config/cassandra.js";

const router = express.Router();

// ========== SẢN PHẨM ==========
router.get("/products", async (req, res) => {
  try {
    const limit = Math.min(parseInt(req.query.limit || "500", 10) || 500, 1000);
    const q = `
      SELECT product_id, name, category, price, image_url, status
      FROM products_by_id
      LIMIT ${limit}
    `;
    const rs = await client.execute(q);
    res.json({ ok: true, data: rs.rows });
  } catch (e) {
    console.error("GET /products error:", e);
    res.status(500).json({ ok: false, error: e.message });
  }
});

// ========== KHUYẾN MÃI ==========
router.get("/promotions/active", async (req, res) => {
  try {
    const day = req.query.day || new Date().toISOString().slice(0, 10);
    const rs = await client.execute(
      `SELECT promo_id, name, type, start_date, end_date
       FROM promotions_active_by_day
       WHERE day=?`,
      [day],
      { prepare: true }
    );
    res.json({ ok: true, data: rs.rows });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

router.get("/promotions/:promoId/products", async (req, res) => {
  try {
    const rs = await client.execute(
      `SELECT product_id, discount_percent, discount_amount, gift_product_id
       FROM products_by_promo
       WHERE promo_id=?`,
      [req.params.promoId],
      { prepare: true }
    );
    res.json({ ok: true, data: rs.rows });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

// ========== CHI TIẾT SẢN PHẨM ==========
router.get("/products/:id", async (req, res) => {
  try {
    const rs = await client.execute(
      `SELECT product_id, name, category, price, image_url, status
       FROM products_by_id
       WHERE product_id=?`,
      [req.params.id],
      { prepare: true }
    );
    res.json({ ok: true, data: rs.first() || null });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

// ========== CHECKOUT ==========
router.post("/checkout", async (req, res) => {
  try {
    const {
      customer_id: rawCustomerId,
      phone,
      name,
      address,
      note,
      payment,
    } = req.body;

    const phoneNumber = (phone || "").trim();
    const fullName = (name || "").trim();

    if (!phoneNumber) {
      return res.json({ ok: false, error: "Vui long nhap so dien thoai" });
    }
    if (!fullName) {
      return res.json({ ok: false, error: "Vui long nhap ho ten" });
    }

    const cartOwner = (rawCustomerId || "").trim() || phoneNumber;

    const cart = await client.execute(
      "SELECT * FROM cart_by_customer WHERE customer_id=?",
      [cartOwner],
      { prepare: true }
    );

    if (!cart.rows.length) {
      return res.json({ ok: false, error: "Gio hang trong" });
    }

    const accountRs = await client.execute(
      "SELECT full_name FROM accounts_by_phone WHERE phone=?",
      [phoneNumber],
      { prepare: true }
    );

    if (!accountRs.rows.length) {
      await client.execute(
        "INSERT INTO accounts_by_phone (phone, full_name) VALUES (?, ?)",
        [phoneNumber, fullName],
        { prepare: true }
      );
    } else {
      const currentName = accountRs.rows[0].full_name || "";
      if (fullName && fullName !== currentName) {
        await client.execute(
          "UPDATE accounts_by_phone SET full_name=? WHERE phone=?",
          [fullName, phoneNumber],
          { prepare: true }
        );
      }
    }

    let total = 0;
    for (const it of cart.rows) {
      const priceRow = await client.execute(
        "SELECT price FROM products_by_id WHERE product_id=?",
        [it.product_id],
        { prepare: true }
      );
      if (priceRow.rows.length) {
        total += Number(priceRow.rows[0].price || 0) * it.qty;
      }
    }

    const discount = 0;
    const final = total - discount;

    const order_id = "ORD" + Date.now();
    const nowVN = new Date().toLocaleString("sv-SE", {
      timeZone: "Asia/Ho_Chi_Minh",
    });
    const order_date = nowVN.replace(" ", "T");

    await client.execute(
      `INSERT INTO orders_by_id
       (order_id, customer_id, order_date, promo_id, total_amount, discount_amount, final_amount)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [order_id, phoneNumber, order_date, null, total, discount, final],
      { prepare: true }
    );

    await client.execute(
      `INSERT INTO orders_by_customer_date
       (customer_id, order_date, order_id, promo_id, total_amount, discount_amount, final_amount)
       VALUES (?, ?, ?, ?, ?, ?, ?)`,
      [phoneNumber, order_date, order_id, null, total, discount, final],
      { prepare: true }
    );

    await client.execute(
      "DELETE FROM cart_by_customer WHERE customer_id=?",
      [cartOwner],
      { prepare: true }
    );

    res.json({
      ok: true,
      order_id,
      total,
      final,
      order_date,
      customer_id: phoneNumber,
    });
  } catch (err) {
    console.error("Loi khi tao don hang:", err);
    res.status(500).json({ ok: false, error: err.message });
  }
});
// ========== ĐƠN HÀNG ==========
router.get("/orders", async (req, res) => {
  try {
    const { customer_id } = req.query;
    if (!customer_id)
      return res.json({ ok: false, error: "Thiếu customer_id" });
    const rs = await client.execute(
      "SELECT * FROM orders_by_customer_date WHERE customer_id=?",
      [customer_id],
      { prepare: true }
    );
    res.json({ ok: true, data: rs.rows });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ========== GIỎ HÀNG ==========
router.get("/cart", async (req, res) => {
  try {
    const { customer_id } = req.query;
    const rs = await client.execute(
      `SELECT customer_id, added_at, item_id, product_id, qty
       FROM cart_by_customer
       WHERE customer_id=?`,
      [customer_id],
      { prepare: true }
    );
    res.json({ ok: true, data: rs.rows });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

router.post("/cart", async (req, res) => {
  try {
    const { customer_id, item_id, product_id, qty } = req.body;
    const added_at = new Date().toISOString().slice(0, 10);
    await client.execute(
      `INSERT INTO cart_by_customer (customer_id, added_at, item_id, product_id, qty)
       VALUES (?, ?, ?, ?, ?)`,
      [customer_id, added_at, item_id, product_id, qty],
      { prepare: true }
    );
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

router.patch("/cart", async (req, res) => {
  try {
    const { customer_id, added_at, item_id, qty } = req.body;
    await client.execute(
      `UPDATE cart_by_customer
       SET qty=?
       WHERE customer_id=? AND added_at=? AND item_id=?`,
      [qty, customer_id, added_at, item_id],
      { prepare: true }
    );
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

router.delete("/cart", async (req, res) => {
  try {
    const { customer_id, added_at, item_id } = req.body;
    await client.execute(
      `DELETE FROM cart_by_customer
       WHERE customer_id=? AND added_at=? AND item_id=?`,
      [customer_id, added_at, item_id],
      { prepare: true }
    );
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message });
  }
});

export default router;
