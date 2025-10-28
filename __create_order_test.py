import json
from uuid import uuid4
from datetime import datetime
from cassandra.cluster import Cluster
cluster = Cluster(['127.0.0.1'], port=9042)
session = cluster.connect('promo_shop')
from python_api import create_order, OrderPayload
payload = OrderPayload(
    user_id='USR-TEST',
    customer_name='Test User',
    customer_phone='0123456789',
    shipping_address='123 Test Street',
    note='note',
    items=[{'product_id':'MT002','name':'Matcha','price':52000,'quantity':2}],
    summary={
        'subtotal': 104000,
        'discount': 15000,
        'final_total': 89000,
        'final_shipping_fee': 0,
        'applied_promotions': [
            {
                'promotion': {'promo_id': 'SPRING2025', 'title': 'Spring Treats'},
                'tier': {'tier_level': 1, 'label': 'Gi?m 10%'},
                'discount': 15000,
                'shipping_discount': 0,
            }
        ],
        'gifts': []
    }
)
print(create_order(payload))
