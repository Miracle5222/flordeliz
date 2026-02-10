# Transaction System Guide - Flor de Liz

## Overview
The Flor de Liz system automatically records all inventory and sales transactions in the database. This guide explains how transactions are captured and displayed.

---

## Table of Contents
1. [How Transactions Are Created](#how-transactions-are-created)
2. [Transaction Types](#transaction-types)
3. [Database Tables](#database-tables)
4. [Viewing Transactions](#viewing-transactions)
5. [Understanding Labels](#understanding-labels)

---

## How Transactions Are Created

### 1. INVENTORY TRANSACTIONS
Inventory transactions are automatically recorded whenever items are added, removed, or adjusted in the inventory system.

#### A. Stock In (Adding Inventory)
**Where it happens:** Staff → Inventory → Add New Item
- When a staff member adds a new product or material to inventory
- A transaction record is automatically created with type = 'in'
- Records: Product name, quantity added, and current timestamp

**Example:**
```
- Added 100 units of "Hardbound Book"
- Type: Stock In
- Quantity: 100
- Created By: Staff User
- Notes: "Added new inventory item: Hardbound Book"
```

#### B. Stock Out (Selling from Inventory)
**Where it happens:** Staff → Sales → Create Sale
- When a staff member records a direct sale/counter sale
- Inventory is automatically deducted
- A transaction record is created with type = 'out'
- Records: Product sold, quantity sold, sale amount, and timestamp

**Example:**
```
- Sold 5 units of "Colored Bondpaper"
- Type: Stock Out
- Quantity: 5
- Created By: Staff User
- Notes: "Sold 5 pcs of Colored Bondpaper"
```

#### C. Stock Adjustment
**Where it happens:** Manual adjustments or corrections
- Type: 'adjustment'
- Used when inventory needs to be corrected for damages, losses, or corrections

---

### 2. SALES TRANSACTIONS
Sales transactions record all direct sales/counter sales made to customers.

**Where it happens:** Staff → Sales → Create Sale
- When a sale is recorded, a sales_transactions record is created
- Records: Sale date, total amount, notes, and who recorded it
- This is separate from the inventory 'out' transaction

**Example:**
```
- Sale Date: Feb 04, 2026
- Total Amount: ₱2,500.00
- Notes: "Sale recorded for Customer Order"
- Recorded By: Staff User
```

---

## Transaction Types

### INVENTORY TRANSACTION TYPES
| Type | Label | Color | Meaning |
|------|-------|-------|---------|
| `in` | Stock In | Green | Items added to inventory |
| `out` | Stock Out | Red | Items removed from inventory (sold) |
| `adjustment` | Stock Adjustment | Yellow | Inventory corrections/adjustments |

### SALES TRANSACTION TYPES
- **Direct Sales**: Recorded when items are sold directly to customers
- **Counter Sales**: Recorded when items are sold over-the-counter

---

## Database Tables

### `inventory_transactions` Table
Stores all inventory movement records.

```
Fields:
- id: Unique identifier
- product_id: ID of the product sold
- material_id: ID of the material (if applicable)
- transaction_type: 'in', 'out', or 'adjustment'
- quantity: Number of items
- notes: Description of transaction
- created_by: User ID of person who recorded it
- created_at: Timestamp of transaction
```

### `sales_transactions` Table
Stores all direct sales records.

```
Fields:
- id: Unique identifier
- transaction_date: Date of the sale
- total_sales: Amount of the sale
- notes: Description or notes about sale
- created_by: User ID of person who recorded it
- created_at: Timestamp when recorded
```

### `sales` Table
Stores complete order/sale information.

```
Fields:
- id: Order ID
- order_number: Unique order number (ORD-YYYYMMDDHHMMSS)
- customer_id: Customer who placed order
- order_date: When order was placed
- delivery_date: When order will be delivered
- total_amount: Final amount after discounts
- status: 'pending' or 'paid'
- created_at: Timestamp
```

### `sale_items` Table
Stores individual items in each sale.

```
Fields:
- id: Unique identifier
- sale_id: Reference to sales table
- product_id: Product sold
- quantity: How many units
- unit_price: Price per unit
- total_price: quantity × unit_price
```

---

## Viewing Transactions

### Staff View
Navigate to: **Staff Dashboard → Transactions**

Two tabs available:
1. **Inventory Transactions Tab**
   - Shows all stock movements
   - Filter by transaction type (In/Out/Adjustment)
   - Search by item name or description
   - See who recorded each transaction

2. **Sales Transactions Tab**
   - Shows all direct sales
   - View total amount per transaction
   - See transaction notes/descriptions
   - Search across all sales

### Admin View
Navigate to: **Admin Dashboard → Transactions**

Same as staff view but with amber color scheme instead of teal.

---

## Understanding Labels

### Column Headers Explained

#### Inventory Tab
| Column | Meaning |
|--------|---------|
| **Transaction Date** | When the stock movement occurred |
| **Transaction Type** | Stock In / Stock Out / Stock Adjustment |
| **Item Name** | Product or material name |
| **Quantity** | Number of units moved |
| **Description** | Detailed notes about transaction |
| **Recorded By** | Which staff member recorded it |

#### Sales Tab
| Column | Meaning |
|--------|---------|
| **Transaction ID** | Unique ID for this sale |
| **Sale Date** | When the sale was made |
| **Amount** | Total amount of the sale (₱) |
| **Description** | Notes about the sale |
| **Recorded By** | Which staff member recorded it |

#### Recent Sales Summary
| Column | Meaning |
|--------|---------|
| **Sale ID** | Unique sale identifier |
| **Sale Date & Time** | Exact date and time of sale |
| **Items Sold** | How many different products sold |
| **Total Amount** | Total revenue from this sale |

---

## How It All Works Together

### Scenario: Customer Buys Colored Bondpaper

1. **Staff creates a sale:**
   ```
   Product: Colored Bondpaper
   Quantity: 5 units
   Unit Price: ₱10.00
   Total: ₱50.00
   ```

2. **System automatically creates:**
   - **Sales Transaction Record:**
     ```
     ID: (auto-generated)
     Amount: ₱50.00
     Type: Counter Sale
     Notes: "Sale recorded for Colored Bondpaper"
     Date: Feb 04, 2026
     ```

   - **Inventory Transaction Record:**
     ```
     ID: (auto-generated)
     Type: Stock Out
     Item: Colored Bondpaper
     Quantity: -5
     Notes: "Sold 5 pcs of Colored Bondpaper"
     Date: Feb 04, 2026
     ```

   - **Inventory Update:**
     ```
     Colored Bondpaper quantity: 500 → 495
     ```

3. **Both transactions visible in Transactions page**

---

## Statistics Shown

### Inventory Tab Statistics
- **Total Inventory Transactions**: Count of all stock movements
- **Breakdown by type**: How many In/Out/Adjustment transactions

### Sales Tab Statistics
- **Total Transactions**: Number of sales recorded
- **Total Revenue**: Sum of all sales amounts
- **Average Sale**: Total Revenue ÷ Number of Transactions

---

## Key Features

✅ **Automatic Recording**: All transactions auto-recorded when created
✅ **Real-time Data**: See transactions immediately after creation
✅ **Search & Filter**: Find transactions by item name or type
✅ **User Attribution**: Know who recorded each transaction
✅ **Detailed Notes**: Each transaction includes descriptive notes
✅ **Timestamps**: Exact date and time of every transaction
✅ **Statistics**: Quick overview of inventory and sales activity

---

## Troubleshooting

### No Transactions Showing?
1. Check if you've created any inventory items or sales
2. Ensure you're logged in as staff or admin
3. Check browser's developer console for errors

### Wrong Item Names Showing?
- Verify products exist in inventory database
- Check that product names are correct in database

### Missing Transactions?
- Transactions are only created when actual sales/inventory changes occur
- Draft orders do not create transactions until finalized

---

## Summary

The Flor de Liz Transaction System ensures complete traceability of all inventory and sales activities. Every stock movement and sale is automatically recorded with:
- ✅ What happened (transaction type)
- ✅ When it happened (timestamp)
- ✅ Who did it (user attribution)
- ✅ How much (quantity/amount)
- ✅ Why (detailed notes)

This provides complete visibility into business operations and inventory levels.
