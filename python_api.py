from datetime import date, datetime
from decimal import Decimal
from typing import Any, Dict, List, Optional
from uuid import uuid4

import hashlib
import json

from cassandra.cluster import Cluster
from cassandra.query import dict_factory
from fastapi import Body, FastAPI, HTTPException, Query, status
from pydantic import BaseModel, EmailStr


cluster = Cluster(["127.0.0.1"], port=9042)
session = cluster.connect("promo_shop")
session.row_factory = dict_factory

_metadata = cluster.metadata
_promotion_table = None
PROMOTION_HAS_DESCRIPTION = False
try:
    _promotion_table = _metadata.keyspaces["promo_shop"].tables.get("promotions")
except KeyError:
    _promotion_table = None
if _promotion_table:
    PROMOTION_HAS_DESCRIPTION = "description" in _promotion_table.columns

app = FastAPI(title="PromoShop Cassandra API", version="1.0.0")


# =======================================
# Helpers
# =======================================
def hash_password(password: str) -> str:
    return hashlib.sha256(password.encode("utf-8")).hexdigest()


def verify_password(password: str, hashed: str) -> bool:
    return hash_password(password) == hashed


def to_decimal(value: Any) -> Decimal:
    if value is None:
        return Decimal("0")
    if isinstance(value, Decimal):
        return value
    return Decimal(str(value))


def serialize_items(items: List[Dict[str, Any]]) -> str:
    return json.dumps(items, ensure_ascii=False)


def deserialize_items(payload: Optional[str]) -> List[Dict[str, Any]]:
    if not payload:
        return []
    try:
        return json.loads(payload)
    except json.JSONDecodeError:
        return []


def normalize_date_value(value: Optional[Any]) -> Optional[date]:
    if value is None or value == "":
        return None
    if isinstance(value, date):
        return value
    if isinstance(value, datetime):
        return value.date()
    if isinstance(value, str):
        text = value.strip()
        if not text:
            return None
        try:
            return datetime.fromisoformat(text).date()
        except ValueError as exc:
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail=f"Invalid date format: {value}",
            ) from exc
    raise HTTPException(
        status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
        detail="Invalid date value",
    )


# =======================================
# Schemas
# =======================================
class RegisterPayload(BaseModel):
    name: str
    email: EmailStr
    password: str


class LoginPayload(BaseModel):
    email: EmailStr
    password: str


class ProductPayload(BaseModel):
    product_id: str
    name: str
    price: int
    stock: int = 0
    category: Optional[str] = None
    status: Optional[str] = "active"
    image_url: Optional[str] = None


class PromotionPayload(BaseModel):
    promo_id: str
    title: str
    type: str = "tiered"
    min_order: Optional[int] = 0
    discount_percent: Optional[int] = None
    reward_type: str = "discount"
    max_discount_amount: Optional[int] = None
    start_date: Optional[str] = None
    end_date: Optional[str] = None
    status: str = "active"
    auto_apply: bool = True
    stackable: bool = False
    description: Optional[str] = None


class TierPayload(BaseModel):
    tier_level: int
    label: Optional[str] = None
    min_value: int
    discount_percent: Optional[int] = None
    discount_amount: Optional[int] = None
    freeship: bool = False
    gift_product_id: Optional[str] = None
    gift_quantity: Optional[int] = None
    combo_description: Optional[str] = None


class OrderPayload(BaseModel):
    user_id: Optional[str] = None
    customer_name: str
    customer_phone: str
    shipping_address: str
    note: Optional[str] = None
    items: List[Dict[str, Any]]
    summary: Dict[str, Any]


# =======================================
# Auth endpoints
# =======================================
@app.post("/api/v1/auth/register", status_code=status.HTTP_201_CREATED)
def register_user(payload: RegisterPayload):
    email = payload.email.lower()
    existing = session.execute(
        "SELECT email FROM users_by_email WHERE email = %s", (email,)
    ).one()
    if existing:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT, detail="Email đã tồn tại"
        )

    user_id = f"USR-{uuid4().hex[:8].upper()}"
    now = datetime.utcnow()
    hashed = hash_password(payload.password)

    session.execute(
        """
        INSERT INTO users (user_id, name, email, password, role, created_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (user_id, payload.name, email, hashed, "customer", now),
    )
    session.execute(
        """
        INSERT INTO users_by_email (email, user_id, name, password, role, created_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (email, user_id, payload.name, hashed, "customer", now),
    )

    return {"data": {"user_id": user_id}}


@app.post("/api/v1/auth/login")
def login(payload: LoginPayload):
    row = session.execute(
        "SELECT * FROM users_by_email WHERE email = %s", (payload.email.lower(),)
    ).one()
    if not row or not verify_password(payload.password, row["password"]):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Email hoặc mật khẩu không đúng",
        )

    token = uuid4().hex
    user_data = {
        "user_id": row["user_id"],
        "name": row["name"],
        "email": row["email"],
        "role": row.get("role", "customer"),
    }
    return {"data": {"token": token, "user": user_data}}


# =======================================
# User endpoints
# =======================================
@app.get("/api/v1/users")
def list_users():
    rows = session.execute("SELECT * FROM users")
    return {"data": list(rows)}


@app.get("/api/v1/users/{user_id}")
def get_user(user_id: str):
    row = session.execute(
        "SELECT * FROM users WHERE user_id = %s", (user_id,)
    ).one()
    if not row:
        raise HTTPException(status_code=404, detail="Không tìm thấy người dùng")
    return {"data": row}


@app.get("/api/v1/users/lookup")
def lookup_user(email: EmailStr = Query(...)):
    row = session.execute(
        "SELECT * FROM users_by_email WHERE email = %s", (email.lower(),)
    ).one()
    if not row:
        raise HTTPException(status_code=404, detail="Không tìm thấy người dùng")
    return {"data": row}


@app.post("/api/v1/users", status_code=status.HTTP_201_CREATED)
def create_user(payload: Dict[str, Any]):
    user_id = payload.get("user_id") or f"USR-{uuid4().hex[:8].upper()}"
    email = payload.get("email", "").lower()
    hashed = hash_password(payload.get("password", uuid4().hex))
    now = datetime.utcnow()

    session.execute(
        """
        INSERT INTO users (user_id, name, email, password, role, created_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (
            user_id,
            payload.get("name"),
            email,
            hashed,
            payload.get("role", "customer"),
            now,
        ),
    )
    session.execute(
        """
        INSERT INTO users_by_email (email, user_id, name, password, role, created_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (
            email,
            user_id,
            payload.get("name"),
            hashed,
            payload.get("role", "customer"),
            now,
        ),
    )
    return {"data": {"user_id": user_id}}


@app.put("/api/v1/users/{user_id}")
def update_user(user_id: str, payload: Dict[str, Any]):
    existing = session.execute(
        "SELECT * FROM users WHERE user_id = %s", (user_id,)
    ).one()
    if not existing:
        raise HTTPException(status_code=404, detail="Không tìm thấy người dùng")

    email = payload.get("email", existing["email"]).lower()
    hashed = existing["password"]
    if payload.get("password"):
        hashed = hash_password(payload["password"])

    session.execute(
        """
        INSERT INTO users (user_id, name, email, password, role, created_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (
            user_id,
            payload.get("name", existing["name"]),
            email,
            hashed,
            payload.get("role", existing.get("role", "customer")),
            existing.get("created_at"),
        ),
    )
    session.execute(
        """
        INSERT INTO users_by_email (email, user_id, name, password, role, created_at)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (
            email,
            user_id,
            payload.get("name", existing["name"]),
            hashed,
            payload.get("role", existing.get("role", "customer")),
            existing.get("created_at"),
        ),
    )
    return {"data": {"user_id": user_id}}


@app.delete("/api/v1/users/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_user(user_id: str):
    existing = session.execute(
        "SELECT email FROM users WHERE user_id = %s", (user_id,)
    ).one()
    if existing:
        session.execute("DELETE FROM users WHERE user_id = %s", (user_id,))
        session.execute(
            "DELETE FROM users_by_email WHERE email = %s",
            (existing["email"],),
        )
    return {"ok": True}


# =======================================
# Product endpoints
# =======================================
@app.get("/api/v1/products")
def list_products():
    rows = session.execute("SELECT * FROM products_by_id")
    return {"data": list(rows)}


@app.get("/api/v1/products/{product_id}")
def get_product(product_id: str):
    row = session.execute(
        "SELECT * FROM products_by_id WHERE product_id = %s", (product_id,)
    ).one()
    if not row:
        raise HTTPException(status_code=404, detail="Không tìm thấy sản phẩm")
    return {"data": row}


@app.post("/api/v1/products", status_code=status.HTTP_201_CREATED)
def create_product(payload: ProductPayload):
    now = datetime.utcnow()
    session.execute(
        """
        INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            payload.category or "uncategorized",
            payload.product_id,
            payload.name,
            Decimal(payload.price),
            payload.stock,
            payload.image_url,
            payload.status,
            now,
        ),
    )
    session.execute(
        """
        INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            payload.product_id,
            payload.category or "uncategorized",
            payload.name,
            Decimal(payload.price),
            payload.stock,
            payload.image_url,
            payload.status,
            now,
        ),
    )
    return {"data": payload.dict()}


@app.put("/api/v1/products/{product_id}")
def update_product(product_id: str, payload: ProductPayload):
    now = datetime.utcnow()
    session.execute(
        """
        INSERT INTO products (category, product_id, name, price, stock, image_url, status, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            payload.category or "uncategorized",
            product_id,
            payload.name,
            Decimal(payload.price),
            payload.stock,
            payload.image_url,
            payload.status,
            now,
        ),
    )
    session.execute(
        """
        INSERT INTO products_by_id (product_id, category, name, price, stock, image_url, status, updated_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            product_id,
            payload.category or "uncategorized",
            payload.name,
            Decimal(payload.price),
            payload.stock,
            payload.image_url,
            payload.status,
            now,
        ),
    )
    return {"data": payload.dict()}


@app.delete("/api/v1/products/{product_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_product(product_id: str):
    row = session.execute(
        "SELECT category FROM products_by_id WHERE product_id = %s", (product_id,)
    ).one()
    if row:
        session.execute(
            "DELETE FROM products WHERE category = %s AND product_id = %s",
            (row["category"], product_id),
        )
        session.execute(
            "DELETE FROM products_by_id WHERE product_id = %s", (product_id,)
        )
    return {"ok": True}


# =======================================
# Promotion endpoints
# =======================================
def hydrate_promotion(row: Dict[str, Any], include_tiers: bool = False) -> Dict[str, Any]:
    if not row:
        return {}
    data = dict(row)
    if include_tiers:
        tier_rows = session.execute(
            "SELECT * FROM promotion_tiers WHERE promo_id = %s",
            (row["promo_id"],),
        )
        data["tiers"] = list(tier_rows)
    return data


@app.get("/api/v1/promotions")
def list_promotions(with_tiers: int = Query(0, ge=0, le=1)):
    rows = session.execute("SELECT * FROM promotions")
    data = [
        hydrate_promotion(row, include_tiers=bool(with_tiers))
        for row in rows
    ]
    return {"data": data}


@app.get("/api/v1/promotions/{promo_id}")
def get_promotion(promo_id: str, with_tiers: int = Query(1, ge=0, le=1)):
    row = session.execute(
        "SELECT * FROM promotions WHERE promo_id = %s", (promo_id,)
    ).one()
    if not row:
        raise HTTPException(status_code=404, detail="Không tìm thấy khuyến mãi")
    return {"data": hydrate_promotion(row, include_tiers=bool(with_tiers))}


@app.post("/api/v1/promotions", status_code=status.HTTP_201_CREATED)
def create_promotion(payload: PromotionPayload):
    promo_id = payload.promo_id.upper()
    now = datetime.utcnow()
    start_date = normalize_date_value(payload.start_date)
    end_date = normalize_date_value(payload.end_date)
    min_order = Decimal(payload.min_order or 0)
    max_discount = (
        Decimal(payload.max_discount_amount)
        if payload.max_discount_amount is not None
        else None
    )

    columns = [
        "promo_id",
        "title",
        "type",
        "min_order",
        "discount_percent",
        "reward_type",
        "max_discount_amount",
        "start_date",
        "end_date",
        "status",
        "auto_apply",
        "stackable",
        "created_at",
        "updated_at",
    ]
    values = [
        promo_id,
        payload.title,
        payload.type,
        min_order,
        payload.discount_percent,
        payload.reward_type,
        max_discount,
        start_date,
        end_date,
        payload.status,
        payload.auto_apply,
        payload.stackable,
        now,
        now,
    ]
    if PROMOTION_HAS_DESCRIPTION:
        columns.insert(12, "description")
        values.insert(12, payload.description)

    column_sql = ", ".join(columns)
    placeholders = ", ".join(["%s"] * len(columns))
    session.execute(
        f"""
        INSERT INTO promotions ({column_sql})
        VALUES ({placeholders})
        """,
        tuple(values),
    )

    status_start_date = start_date or datetime.utcnow().date()
    session.execute(
        """
        INSERT INTO promotions_by_status (status, start_date, promo_id, title, type, reward_type)
        VALUES (%s, %s, %s, %s, %s, %s)
        """,
        (
            payload.status,
            status_start_date,
            promo_id,
            payload.title,
            payload.type,
            payload.reward_type,
        ),
    )

    response_payload = payload.copy(
        update={
            "promo_id": promo_id,
            "min_order": int(min_order),
            "max_discount_amount": int(max_discount)
            if max_discount is not None
            else None,
            "start_date": start_date.isoformat() if start_date else None,
            "end_date": end_date.isoformat() if end_date else None,
        }
    )
    return {"data": response_payload.dict()}


@app.put("/api/v1/promotions/{promo_id}")
def update_promotion(promo_id: str, payload: PromotionPayload):
    return create_promotion(payload.copy(update={"promo_id": promo_id}))


@app.delete("/api/v1/promotions/{promo_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_promotion(promo_id: str):
    session.execute("DELETE FROM promotions WHERE promo_id = %s", (promo_id,))
    session.execute(
        "DELETE FROM promotions_by_status WHERE promo_id = %s", (promo_id,)
    )
    session.execute(
        "DELETE FROM promotion_tiers WHERE promo_id = %s", (promo_id,)
    )
    return {"ok": True}


@app.get("/api/v1/promotions/{promo_id}/tiers")
def list_tiers(promo_id: str):
    rows = session.execute(
        "SELECT * FROM promotion_tiers WHERE promo_id = %s", (promo_id,)
    )
    return {"data": list(rows)}


@app.post("/api/v1/promotions/{promo_id}/tiers", status_code=status.HTTP_201_CREATED)
def create_tier(promo_id: str, payload: TierPayload):
    session.execute(
        """
        INSERT INTO promotion_tiers (promo_id, tier_level, label, min_value, discount_percent,
            discount_amount, freeship, gift_product_id, gift_quantity, combo_description)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            promo_id,
            payload.tier_level,
            payload.label,
            Decimal(payload.min_value),
            payload.discount_percent,
            Decimal(payload.discount_amount or 0),
            payload.freeship,
            payload.gift_product_id,
            payload.gift_quantity,
            payload.combo_description,
        ),
    )
    return {"data": payload.dict()}


@app.put("/api/v1/promotions/{promo_id}/tiers/{tier_level}")
def update_tier(promo_id: str, tier_level: int, payload: TierPayload):
    return create_tier(promo_id, payload.copy(update={"tier_level": tier_level}))


@app.delete(
    "/api/v1/promotions/{promo_id}/tiers/{tier_level}",
    status_code=status.HTTP_204_NO_CONTENT,
)
def delete_tier(promo_id: str, tier_level: int):
    session.execute(
        "DELETE FROM promotion_tiers WHERE promo_id = %s AND tier_level = %s",
        (promo_id, tier_level),
    )
    return {"ok": True}


# =======================================
# Order endpoints
# =======================================
@app.get("/api/v1/orders")
def list_orders(user_id: Optional[str] = Query(None)):
    if user_id:
        rows = session.execute(
            "SELECT * FROM orders WHERE user_id = %s", (user_id,)
        )
    else:
        rows = session.execute("SELECT * FROM orders_by_id")
    data = []
    for row in rows:
        record = dict(row)
        record["items"] = deserialize_items(record.get("items"))
        data.append(record)
    return {"data": data}


@app.post("/api/v1/orders", status_code=status.HTTP_201_CREATED)
def create_order(payload: OrderPayload):
    order_id = f"ORD-{uuid4().hex[:10].upper()}"
    now = datetime.utcnow()
    summary = payload.summary

    session.execute(
        """
        INSERT INTO orders (user_id, created_at, order_id, items, total, discount, final_amount,
            shipping_fee, status, promo_id, applied_tier, note)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            payload.user_id or "GUEST",
            now,
            order_id,
            serialize_items(payload.items),
            Decimal(summary.get("subtotal", 0)),
            Decimal(summary.get("discount", 0)),
            Decimal(summary.get("final_total", 0)),
            Decimal(summary.get("final_shipping_fee", 0)),
            "pending",
            summary.get("applied_promotions", [{}])[0].get("promotion", {}).get("promo_id"),
            summary.get("applied_promotions", [{}])[0].get("tier", {}).get("tier_level"),
            payload.note,
        ),
    )
    session.execute(
        """
        INSERT INTO orders_by_id (order_id, user_id, created_at, items, total, discount,
            final_amount, shipping_fee, status, promo_id, applied_tier, note)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            order_id,
            payload.user_id or "GUEST",
            now,
            serialize_items(payload.items),
            Decimal(summary.get("subtotal", 0)),
            Decimal(summary.get("discount", 0)),
            Decimal(summary.get("final_total", 0)),
            Decimal(summary.get("final_shipping_fee", 0)),
            "pending",
            summary.get("applied_promotions", [{}])[0].get("promotion", {}).get("promo_id"),
            summary.get("applied_promotions", [{}])[0].get("tier", {}).get("tier_level"),
            payload.note,
        ),
    )

    # ghi log khuyến mãi
    for applied in summary.get("applied_promotions", []):
        log_id = uuid4()
        session.execute(
            """
            INSERT INTO promotion_logs (promo_id, applied_at, log_id, order_id, user_id,
                tier_level, discount_amount, freeship, reward)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            """,
            (
                applied["promotion"].get("promo_id"),
                now,
                log_id,
                order_id,
                payload.user_id or "GUEST",
                applied["tier"].get("tier_level"),
                Decimal(applied.get("discount", 0)),
                applied.get("shipping_discount", 0) > 0,
                json.dumps(applied.get("gift"), ensure_ascii=False),
            ),
        )
        session.execute(
            """
            INSERT INTO promotion_logs_by_order (order_id, applied_at, log_id, promo_id,
                tier_level, discount_amount)
            VALUES (%s, %s, %s, %s, %s, %s)
            """,
            (
                order_id,
                now,
                log_id,
                applied["promotion"].get("promo_id"),
                applied["tier"].get("tier_level"),
                Decimal(applied.get("discount", 0)),
            ),
        )

    return {"data": {"order_id": order_id}}


# =======================================
# Logs & dashboard
# =======================================
@app.get("/api/v1/promotion-logs")
def promotion_logs(promo_id: Optional[str] = Query(None)):
    if promo_id:
        rows = session.execute(
            "SELECT * FROM promotion_logs WHERE promo_id = %s", (promo_id,)
        )
    else:
        rows = session.execute("SELECT * FROM promotion_logs_by_order")
    return {"data": list(rows)}


@app.get("/api/v1/dashboard")
def dashboard():
    promos = session.execute("SELECT COUNT(*) AS total FROM promotions").one()
    products = session.execute(
        "SELECT COUNT(*) AS total FROM products_by_id"
    ).one()
    orders = session.execute("SELECT COUNT(*) AS total FROM orders_by_id").one()
    discount_total = session.execute(
        "SELECT SUM(discount_amount) AS total FROM promotion_logs"
    ).one()

    top_promos = session.execute(
        """
        SELECT promo_id, COUNT(*) AS usage, SUM(discount_amount) AS discount_amount
        FROM promotion_logs
        GROUP BY promo_id
        LIMIT 5
        """
    )

    return {
        "data": {
            "promotions_active": promos["total"] if promos else 0,
            "products": products["total"] if products else 0,
            "orders": orders["total"] if orders else 0,
            "discount_amount": (discount_total["total"] or 0)
            if discount_total
            else 0,
            "top_promotions": list(top_promos),
        }
    }
