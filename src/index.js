// src/index.js
import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

// Cassandra check
import { client as cassClient, healthcheck } from "./config/cassandra.js";

// Repositories
import { PromoRepo } from "./repositories/promoRepo.js";
import { AdminRepo } from "./repositories/adminRepo.js";
import { ProductRepo } from "./repositories/productRepo.js";
import { CodeRepo } from "./repositories/codeRepo.js";
import { OrderRepo } from "./repositories/orderRepo.js";
import { AccountRepo } from "./repositories/accountRepo.js";

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

const __dirname = path.dirname(fileURLToPath(import.meta.url));

// ====== SERVE UI ======
app.use("/admin", express.static(path.join(__dirname, "./ui/admin")));
app.use("/shop", express.static(path.join(__dirname, "./ui/customer")));

app.get("/admin", (_, res) => {
  res.redirect("/admin/dashboard.html");
});

// ✅ Dynamic import cho ESM
const { default: customerRouter } = await import("./routes/customer.js");
app.use("/api/customer", customerRouter);

// Root UI
app.get("/", (_, res) => {
  res.sendFile(path.join(__dirname, "../public/index.html"));
});

// ====== HEALTH CHECK ======
app.get("/health", async (_, res) => {
  try {
    const ver = await healthcheck();
    res.json({ ok: true, cassandra_version: ver });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ====== READ-ONLY API ======
app.get("/promos/active", async (req, res) => {
  try {
    const { day } = req.query;
    if (!day)
      return res.status(400).json({ ok: false, msg: "Missing day=YYYY-MM-DD" });
    const rows = await PromoRepo.getActivePromosByDay(day);
    res.json({ ok: true, data: rows });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.get("/promos/type/:type", async (req, res) => {
  try {
    const rows = await PromoRepo.getPromosByType(req.params.type);
    res.json({ ok: true, data: rows });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.get("/promos/:promoId/products", async (req, res) => {
  try {
    const rows = await PromoRepo.getProductsInPromo(req.params.promoId);
    res.json({ ok: true, data: rows });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

// ====== ADMIN API ======
app.get("/admin/promos/:promoId", async (req, res) => {
  try {
    const row = await AdminRepo.getPromoById(req.params.promoId);
    if (!row) return res.status(404).json({ ok: false, msg: "Not found" });
    res.json({ ok: true, data: row });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.post("/admin/promos", async (req, res) => {
  try {
    await AdminRepo.createPromo(req.body);
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.put("/admin/promos/:promoId", async (req, res) => {
  try {
    await AdminRepo.updatePromo(req.params.promoId, req.body);
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.delete("/admin/promos/:promoId", async (req, res) => {
  try {
    await AdminRepo.deletePromo(req.params.promoId);
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.get("/admin/promos/:promoId/products", async (req, res) => {
  try {
    const rows = await AdminRepo.listProductsInPromo(req.params.promoId);
    res.json({ ok: true, data: rows });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.post("/admin/promos/:promoId/products", async (req, res) => {
  try {
    const body = { ...req.body, promo_id: req.params.promoId };
    await AdminRepo.addProductToPromo(body);
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

app.delete("/admin/promos/:promoId/products/:productId", async (req, res) => {
  try {
    await AdminRepo.removeProductFromPromo(
      req.params.promoId,
      req.params.productId
    );
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, msg: e.message });
  }
});

// ====== PRODUCT API ======
function safeResponse(fn) {
  return async (req, res) => {
    try {
      const result = await fn(req, res);
      res.json(result ?? []);
    } catch (err) {
      console.error("❌ API error:", err.message);
      res.status(500).json({ error: err.message });
    }
  };
}

app.get("/api/products", safeResponse(async () => await ProductRepo.getAll()));
app.post("/api/products", safeResponse(async (req) => await ProductRepo.create(req.body)));
app.put("/api/products/:id", safeResponse(async (req) => await ProductRepo.update(req.params.id, req.body)));
app.delete("/api/products/:id", safeResponse(async (req) => await ProductRepo.delete(req.params.id)));

app.get(
  "/api/promo-products",
  safeResponse(async (req) => {
    const { promo_id: promoId } = req.query;
    if (promoId) {
      return await AdminRepo.listProductsInPromo(promoId);
    }
    return await AdminRepo.listAllPromoProducts();
  })
);
app.post(
  "/api/promo-products",
  safeResponse(async (req) => await AdminRepo.addProductToPromo(req.body))
);
app.put(
  "/api/promo-products/:promoId/:productId",
  safeResponse(async (req) =>
    await AdminRepo.updateProductInPromo(
      req.params.promoId,
      req.params.productId,
      req.body
    )
  )
);
app.delete(
  "/api/promo-products/:promoId/:productId",
  safeResponse(async (req) =>
    await AdminRepo.removeProductFromPromo(req.params.promoId, req.params.productId)
  )
);

app.get("/api/promos", safeResponse(async () => await AdminRepo.getAllPromos()));
app.get("/api/promos/:id", safeResponse(async (req) => await AdminRepo.getPromoById(req.params.id)));
app.post("/api/promos", safeResponse(async (req) => await AdminRepo.createPromo(req.body)));
app.put("/api/promos/:id", safeResponse(async (req) => await AdminRepo.updatePromo(req.params.id, req.body)));
app.delete("/api/promos/:id", safeResponse(async (req) => await AdminRepo.deletePromo(req.params.id)));

app.get("/api/codes", safeResponse(async () => await CodeRepo.getAllCodes()));
app.post("/api/codes", safeResponse(async (req) => await CodeRepo.create(req.body)));
app.put("/api/codes/:code", safeResponse(async (req) => await CodeRepo.update(req.params.code, req.body)));
app.delete("/api/codes/:code", safeResponse(async (req) => await CodeRepo.delete(req.params.code)));

app.get(
  "/api/orders",
  safeResponse(async (req) => {
    const { promo_id: promoId, customer_id: customerId } = req.query;
    if (promoId) return await OrderRepo.getByPromo(promoId);
    if (customerId) return await OrderRepo.getByCustomer(customerId);
    return await OrderRepo.getAll();
  })
);

app.get("/api/accounts", safeResponse(async () => await AccountRepo.getAll()));
app.post(
  "/api/accounts",
  safeResponse(async (req) => await AccountRepo.create(req.body))
);
app.put(
  "/api/accounts/:phone",
  safeResponse(async (req) => await AccountRepo.update(req.params.phone, req.body))
);
app.delete(
  "/api/accounts/:phone",
  safeResponse(async (req) => await AccountRepo.remove(req.params.phone))
);

// ====== START SERVER ======
const port = process.env.PORT || 8080;

try {
  const ver = await healthcheck();
  console.log(`✅ Connected to Cassandra version ${ver}`);
} catch (err) {
  console.warn("⚠️ Cassandra not reachable:", err.message);
}

app.listen(port, () => {
  console.log(`🚀 Server running at: http://localhost:${port}`);
});
