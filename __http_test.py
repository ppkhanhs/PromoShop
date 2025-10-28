import requests
payload = {
    'user_id': 'USR-HTTP',
    'customer_name': 'Http Test',
    'customer_phone': '0123',
    'shipping_address': 'Addr',
    'note': 'note',
    'items': [
        {'product_id': 'MT002', 'name': 'Matcha', 'price': 52000, 'quantity': 2}
    ],
    'summary': {
        'subtotal': 104000,
        'discount': 15000,
        'final_total': 89000,
        'final_shipping_fee': 0,
        'applied_promotions': [
            {
                'promotion': {'promo_id': 'SPRING2025', 'title': 'Spring'},
                'tier': {'tier_level': 1, 'label': 'Gi?m 10%'},
                'discount': 15000,
                'shipping_discount': 0
            }
        ],
        'gifts': []
    }
}
resp = requests.post('http://127.0.0.1:8001/api/v1/orders', json=payload, timeout=10)
print(resp.status_code)
print(resp.text)
