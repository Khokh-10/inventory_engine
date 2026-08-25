# 3. Domain model

erDiagram
  CUSTOMERS ||--o{ ORDERS : places
  PRODUCTS ||--o{ ORDER_ITEMS : appears_in
  ORDERS ||--o{ ORDER_ITEMS : contains
  WAREHOUSES ||--o{ INVENTORIES : stores
  PRODUCTS ||--o{ INVENTORIES : stocked_as
  ORDERS ||--o{ RESERVATIONS : has
  RESERVATIONS ||--o{ RESERVATION_ITEMS : contains
  INVENTORIES ||--o{ RESERVATION_ITEMS : allocates
  ORDERS ||--o{ SHIPMENTS : fulfills
  SHIPMENTS ||--o{ SHIPMENT_ITEMS : contains
  RESERVATION_ITEMS ||--o{ SHIPMENT_ITEMS : sourced_by
  SHIPMENTS ||--o{ SHIPMENT_WEBHOOKS : receives
  SHIPMENTS ||--o{ SHIPMENT_HISTORIES : records
  INVENTORIES ||--o{ INVENTORY_LEDGERS : records

Customer, Product, and Warehouse are catalog/location records. Inventory uniquely identifies a product at a warehouse and stores total, available, reserved, picked, shipped, and version. Orders contain order items and relate to reservations and shipments. Reservations allocate inventory through reservation items. Shipments originate from consumed reservations and retain per-item shipped/remaining quantities. Webhooks and histories explain external and operational events. Ledger entries explain inventory movements.

Evidence: app/Models/ and the corresponding create_* migrations.
