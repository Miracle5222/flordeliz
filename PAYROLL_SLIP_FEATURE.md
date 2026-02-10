# Payroll Slip Feature

## Overview
The Payroll Slip feature allows administrators to generate and print professional payroll slips for employees. The slips are designed to be clean and print-friendly without any unnecessary HTML buttons or interface elements.

## Features
- **Professional Design**: Clean, formal payroll slip layout
- **Print-Optimized**: Automatically hides navigation and UI buttons when printing
- **Responsive**: Works on mobile and desktop devices
- **Complete Information**: 
  - Employee details (name, position)
  - Pay period information
  - Earnings breakdown (base pay, overtime pay)
  - Deductions summary
  - Net pay calculation
  - Additional notes

## Accessing the Payroll Slip

### Method 1: From Payroll List
1. Navigate to **Admin → Payroll → View Payroll**
2. In the payroll list table, locate the employee's record
3. Click the **"Slip"** action button (purple link)
4. The payroll slip will open in a new view

### Method 2: Direct Navigation Menu
1. Navigate to **Admin → Payroll → Payroll Slip**
2. You can also access it by going to `/admin/payroll/slip.php?id=[payroll_id]`

### Method 3: From Payroll Details
1. Open any payroll record by clicking **"View"**
2. Click **"Print"** button (the slip display supports printing)

## Printing the Payroll Slip

### Steps to Print:
1. Open the payroll slip by clicking "Slip" from the payroll list
2. Click the **"Print Slip"** button at the top right
3. Your browser's print dialog will open
4. Select your printer and click "Print"
5. Save as PDF is recommended for records

### What Gets Printed:
✓ Company name and contact information
✓ Employee name and position
✓ Pay period dates
✓ Days worked and overtime hours
✓ Earnings breakdown
✓ Deductions
✓ Net pay
✓ Additional notes (if any)

### What Does NOT Print:
✗ Navigation bars
✗ Print/Back buttons
✗ Any HTML UI elements
✗ Page navigation controls

## Payroll Slip Layout

The payroll slip contains the following sections:

### Header
- Company name (Flor de Liz)
- Company address and contact
- "PAYROLL SLIP" title

### Employee Information
- Full name
- Position
- Pay period start date
- Pay period end date
- Days worked
- Overtime hours

### Earnings and Deductions Table
| Item | Amount |
|------|--------|
| Base Pay | ₱XXX.XX |
| Overtime Pay | ₱XXX.XX |
| **Deductions** | **₱XXX.XX** |
| **NET PAY** | **₱XXX.XX** |

### Summary Section
- Income Summary (Gross Pay)
- Deductions Summary
- Net Pay highlighted in green

### Footer
- Generation timestamp
- Note indicating no signature required

## Customization

To customize company information in the payroll slip, edit the following variables in `/admin/payroll/slip.php` (lines 31-33):

```php
$company_name = "Flor de Liz";
$company_address = "Your Company Address";
$company_contact = "Your Contact Info";
```

## Browser Compatibility
- Chrome/Chromium (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers (responsive design)

## Technical Details
- **File Location**: `/admin/payroll/slip.php`
- **Database Query**: Joins payroll, employees, and users tables
- **Authentication**: Admin role required
- **URL Parameter**: `?id=[payroll_id]`
- **Styling**: Pure CSS (no external frameworks in print mode)

## Notes
- The payroll slip automatically redirects to the payroll list if an invalid payroll ID is provided
- All currency amounts are formatted in Philippine Peso (₱)
- Dates are formatted in "Month Day, Year" format
- The document is generated dynamically from database records
