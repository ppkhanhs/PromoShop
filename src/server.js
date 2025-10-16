import express from "express";
import cors from "cors";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

// Cassandra client
import { client as cassClient, healthcheck } from "./config/cassandra.js";

// Repositories
import { PromoRepo } from "./repositories/promoRepo.js";
import { AdminRepo } from "./repositories/adminRepo.js";
import { ProductRepo } from "./repositories/productRepo.js";
import { CodeRepo } from "./repositories/codeRepo.js";

// Routes
import customerRouter from "./routes/customer.js";

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

// ====== Path setup ======
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const port = process.env.PORT || 8080;

// ====== SERVE UI ======
// Admin UI: http://localhost:8080/admin
app.use("/admin", express.static(path.join(__dirname, "../public")));

// Customer UI: http://localhost:8080/shop
app.use("/shop", express.static(path.join(__dirname, "./ui/customer")));

// ====== API ROUTES ======
app.use("/api/customer", customerRouter);

// ====== HEALTH CHECK ======
app.get("/health", async (_, res) => {
  try {
    const ver = await healthcheck();
    res.json({ ok: true, cassandra_version: ver });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ====== ADMIN API ======
app.get("/api/promos", async (_, res) => {
  const data = await AdminRepo.getAllPromos();
  res.json({ ok: true, data });
});

app.get("/api/products", async (_, res) => {
  const data = await ProductRepo.getAll();
  res.json({ ok: true, data });
});

app.get("/api/codes", async (_, res) => {
  const data = await CodeRepo.getAllCodes();
  res.json({ ok: true, data });
});

// ====== STATIC ROOT ======
app.get("/", (_, res) => {
  res.sendFile(path.join(__dirname, "../public/index.html"));
});

// ====== START SERVER ======
try {
  const ver = await healthcheck();
  console.log(`✅ Connected to Cassandra version ${ver}`);
} catch (err) {
  console.warn("⚠️ Cassandra not reachable:", err.message);
}

app.listen(port, () =>
  console.log(`🚀 Server running at: http://localhost:${port}`)
);
