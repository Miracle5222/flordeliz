# 📊 FLORDELIZ TRANSACTION SYSTEM - COMPLETE GUIDE

## Overview
The Flor de Liz transaction system tracks all inventory and sales activities in the business. Every movement of products and every sale is automatically recorded and can be reviewed by staff and administrators.

---

## 📁 Transaction Types

### 1. Inventory Transactions
Track the movement of products and materials in and out of stock.

**Types:**
- **Stock In (IN)**: Items received from suppliers
- **Stock Out (OUT)**: Items sold to customers
- **Adjustment**: Inventory corrections for damage, loss, or discrepancies

**Fields Recorded:**
- Transaction Type (In/Out/Adjustment)
- Product/Material Name
- Quantity
- Notes (reason, supplier, customer, etc.)
- Staff Member (who recorded it)
- Date & Time

### 2. Sales Transactions
Record of all money coming in from sales.

**Fields Recorded:**
- Transaction Date
- Total Sales Amount
- Order Reference (if applicable)
- Notes
- Staff Member (who recorded it)
- Date & Time

---

## 🗄️ Database Tables

### inventory_transactions Table
```sql
CREATE TABLE `inventory_transactions` (
  `id` int(11) PRIMARY KEY,
  `product_id` int(11) - Links to products table
  `material_id` int(11) - Links to inventory_materials table
  `transaction_type` enum('in','out','adjustment')
  `quantity` int(11) - Units affected
  `notes` text - Reason/details
  `created_by` int(11) - Staff member who recorded it
  `created_at` timestamp - When recorded
)
```

### sales_transactions Table
```sql
CREATE TABLE `sales_transactions` (
  `id` int(11) PRIMARY KEY,
  `order_id` int(11) - Links to orders (optional)
  `transaction_date` date - Date of sale
  `total_sales` decimal(12,2) - Amount earned
  `notes` text - Order details
  `created_by` int(11) - Staff member who recorded it
  `created_at` timestamp - When recorded
)
```

### sales Table (Supporting)
```sql
CREATE TABLE `sales` (
  `id` int(11) PRIMARY KEY,
  `sale_date` datetime - When sale occurred
  `total_amount` decimal(12,2) - Total value
  `created_at` timestamp
)
```

### sale_items Table (Supporting)
```sql
CREATE TABLE `sale_items` (
  `id` int(11) PRIMARY KEY,
  `sale_id` int(11) - Links to sales
  `inventory_id` int(11) - Product sold
  `quantity` int(11) - How many
  `unit_price` decimal(12,2)
  `subtotal` decimal(12,2)
)
```

---

## 🔄 How Transactions Are Created

### Inventory Transactions Creation

#### Method 1: Adding to Inventory (Staff)
1. Navigate to: **Staff > Inventory > Add Item**
2. Select product/material
3. Enter quantity received
4. Add notes (supplier name, purchase details)
5. Submit → **Transaction recorded as "IN"**

#### Method 2: Creating a Sale (Automatic)
1. Navigate to: **Staff > Sales > Create Sale**
2. Select items to sell
3. Enter quantities
4. Complete sale → **Automatic "OUT" transactions created** for each item

#### Method 3: Manual Adjustment
1. Navigate to: **Staff > Inventory > Add Transaction**
2. Select "Adjustment" type
3. Enter quantity (negative for reductions)
4. Add reason (damage, loss, correction)
5. Submit → **Transaction recorded as "ADJUSTMENT"**

### Sales Transactions Creation

**Automatic Process:**
1. Staff creates a sale order (Staff > Sales > Create)
2. Adds items and prices
3. Submits sale
4. System automatically creates:
   - Entry in `sales` table
   - Entries in `sale_items` table
   - "OUT" transactions in `inventory_transactions` for each item
   - Entry in `sales_transactions` with total amount

---

## 📊 Viewing Transactions

### For Staff Users
**Path:** Staff Dashboard > Transactions

**Features:**
- View all inventory transactions (Stock In/Out/Adjustments)
- View all sales transactions
- Statistics showing:
  - Total inventory transactions
  - Total inventory movements (In/Out/Adjustments)
  - Total sales records
  - Total revenue
- Search by date, staff member, product
- Tab-based navigation (Inventory vs. Sales)
- Filter by transaction type

**Color Scheme:** Teal theme

### For Admin Users
**Path:** Admin Dashboard > Transactions

**Features:**
- Complete audit trail of ALL transactions
- All staff member transactions visible
- Full reporting capabilities
- Same filtering and search as staff
- Revenue analytics
- Inventory movement summary

**Color Scheme:** Amber theme

---

## 📈 Sample Data Included

### 7 Inventory Transactions
| Type | Item | Qty | Reason |
|------|------|-----|--------|
| IN | Hardbound Books | 50 | Stock received |
| OUT | Softbound Books | 25 | Customer sale |
| IN | Carbonless Paper | 10 | New material |
| ADJUSTMENT | Receipt (1 dozen) | -5 | Damaged units |
| OUT | Hardbound Books | 15 | Bulk order |
| OUT | Colored Bondpaper | 100 | Printing job |
| IN | Receipt Pads | 20 | Restock |

### 4 Sales Records
| Date | Amount |
|------|--------|
| Feb 1, 2026 | ₱5,250.00 |
| Feb 2, 2026 | ₱3,500.00 |
| Feb 3, 2026 | ₱8,750.00 |
| Feb 4, 2026 | ₱4,200.00 |

### 5 Sales Transactions
- Feb 1: ₱5,250.00 - Hardbound books bulk
- Feb 2: ₱3,500.00 - Softbound & receipts
- Feb 3: ₱8,750.00 - Mixed products
- Feb 4: ₱4,200.00 - Sari-sari store bulk
- Feb 4: ₱2,800.00 - Printing materials

**Total Revenue:** ₱24,500.00

---

## ✅ Data Integrity Features

### Automatic Recording
- Every transaction is timestamped (created_at)
- Every transaction records who made it (created_by)
- No manual editing of transaction history (audit trail)

### Linked Data
- Inventory transactions link to products/materials
- Sales transactions link to orders
- Sale items link to inventory
- Complete traceability

### Statistics Verification
- Staff can see totals in transactions page
- Admin can audit all records
- Search to find specific transactions

---

## 🎯 Quick Access Paths

| Task | Path | Action |
|------|------|--------|
| View Staff Transactions | Staff > Transactions | See all movements |
| View Admin Transactions | Admin > Transactions | Complete audit |
| Create Sale | Staff > Sales > Create | Records both inventory OUT and sales |
| Add Inventory | Staff > Inventory > Add | Records inventory IN |
| View Dashboard | Admin Dashboard | Overall statistics |
| Create Payroll | Admin > Payroll > Create | Use transaction data |

---

## 🔍 How to Search Transactions

### In Transaction Pages
1. Go to Transactions view
2. **By Type Tab:** Click "Inventory" or "Sales" tab
3. **By Search:** Type in search box
   - Search by date, product name, customer, amount
   - Real-time filtering as you type
4. **For Inventory:** Filter by In/Out/Adjustment using dropdown

---

## 📊 Using Transaction Data for Reporting

### For Sales Reports
- Sales Transactions page shows all money earned
- Filter by date range for period reports
- Total amount automatically calculated
- Export for accounting

### For Inventory Reports
- Inventory Transactions shows all movements
- Calculate turnover (total out / period)
- Identify slow-moving items
- Plan restocking based on "IN" transactions

### For Payroll
- Check attendance records match transaction dates
- Verify daily transactions for performance reviews
- Confirm staff overtime hours

---

## ⚠️ Important Notes

1. **Automatic Recording**: Sales automatically create inventory transactions
2. **No Deletion**: Transactions cannot be deleted (audit trail)
3. **Timestamps**: All times are system time (UTC+8 or as configured)
4. **Staff Attribution**: Always shows who recorded the transaction
5. **Notes Required**: Always add notes explaining the transaction

---

## 🚀 Next Steps

1. **Review Sample Data**: Go to Transactions page and explore the sample data
2. **Create New Sale**: Go to Staff > Sales > Create to practice
3. **Check Reports**: View Admin > Transactions for complete records
4. **Set Up Alerts**: Configure inventory alerts for low stock
5. **Train Staff**: Show staff how to record transactions properly

---

## 📞 Support

For questions about the transaction system:
- Check the sample data in the Transactions page
- Review transaction details and notes
- Contact system administrator

---

**Last Updated:** February 4, 2026
**System:** Flor de Liz Business Management
