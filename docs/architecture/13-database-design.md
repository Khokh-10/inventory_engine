# 13. Database design

Business tables: customers, products, warehouses, inventories, orders, order_items, reservations, reservation_items, shipments, shipment_items, shipment_webhooks, inventory_ledgers, and shipment_histories.

Key correctness constraints: unique customer email, product SKU, warehouse code, and inventories(warehouse_id, product_id); globally unique shipment_webhooks.event_id; foreign keys with cascade delete; indexes on statuses, timestamps, foreign keys, references, and tracking number. Quantities are unsigned integers; money uses decimal(12,2); JSON fields store webhook/history data.

The schema stores status strings and models cast them to PHP enums. It does not encode full transition graphs, bucket-sum invariants, or shipment-item arithmetic as CHECK constraints.

erDiagram
  CUSTOMERS ||--o{ ORDERS : customer_id
  ORDERS ||--o{ ORDER_ITEMS : order_id
  PRODUCTS ||--o{ ORDER_ITEMS : product_id
  WAREHOUSES ||--o{ INVENTORIES : warehouse_id
  PRODUCTS ||--o{ INVENTORIES : product_id
  ORDERS ||--o{ RESERVATIONS : order_id
  RESERVATIONS ||--o{ RESERVATION_ITEMS : reservation_id
  INVENTORIES ||--o{ RESERVATION_ITEMS : inventory_id
  ORDERS ||--o{ SHIPMENTS : order_id
  SHIPMENTS ||--o{ SHIPMENT_ITEMS : shipment_id
  SHIPMENTS ||--o{ SHIPMENT_WEBHOOKS : shipment_id
  SHIPMENTS ||--o{ SHIPMENT_HISTORIES : shipment_id
  INVENTORIES ||--o{ INVENTORY_LEDGERS : inventory_id

Evidence: database/migrations/ and app/Models/.
