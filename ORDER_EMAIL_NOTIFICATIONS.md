# Order Status Email Notifications

## Overview
The Order Status Email Notification feature automatically sends professional email updates to customers whenever their order status changes. This keeps customers informed about their order progress and provides them with important details.

## Features

### Automatic Email Notifications
- **Triggered on Status Change**: Email is sent whenever an order status is changed from one status to another
- **Customer-Friendly Content**: Professional HTML emails with clear formatting
- **Complete Order Information**: Includes order details, items, amounts, and delivery information
- **Status-Specific Messages**: Different messages for each status type

### Email Contents
Each email includes:
- Customer name (personalized greeting)
- Order status badge with appropriate color coding
- Status-specific message
- Order ID and creation date
- Itemized list of products with quantities and prices
- Order total amount
- Delivery date (if available)
- Delivery address (if available)
- Company contact information
- Professional footer

### Status Types and Messages

| Status | Message |
|--------|---------|
| **Pending** | "Your order has been received and is pending confirmation." |
| **In Progress** | "Your order is now being prepared. Thank you for your patience." |
| **Paid** | "Payment for your order has been received. We will proceed with processing." |
| **Completed** | "Your order has been completed and is ready for pickup or delivery." |
| **Cancelled** | "Your order has been cancelled. Please contact us if you have any questions." |

## How It Works

### Method 1: Form Submission (staff/orders/view.php)
When updating order details through the order edit form:
1. Click "Edit" button on an order
2. Change the status dropdown
3. Click "Save Changes"
4. Email is automatically sent if status changed
5. Confirmation message displays after successful update

### Method 2: AJAX Status Update (staff/orders/update_status.php)
When changing status via the quick status change buttons:
1. Click status change button on order view
2. Confirm the new status
3. Email is automatically sent via AJAX request
4. Status updates without page reload

## Email Sending Requirements

### Prerequisites
- Customer must have an email address in the system
- Email configuration must be properly set up in `/config/email.php`
- PHPMailer library must be installed (included in `/vendor/`)
- SMTP credentials must be valid

### Configuration File
**Location**: `/config/email.php`

```php
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password',
    'secure' => 'tls',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Flor de Liz'
];
```

## Technical Details

### Files Modified
1. **staff/orders/update_status.php** - AJAX endpoint for status updates with email integration
2. **staff/orders/view.php** - Form submission handler with email integration

### Database
- Uses existing `orders`, `customers`, and `order_items` tables
- Requires `customers.email` field to be populated

### Email Library
- **PHPMailer** (vendor/phpmailer/)
- SMTP protocol with TLS encryption
- Exception handling for failed email sending

## Error Handling

### What Happens If Email Fails
- **Form Submission**: Order is still updated successfully. Email failure is logged but doesn't block the transaction.
- **AJAX Request**: Status is still updated. Email error is logged silently.

### Logging
Failed email attempts are logged to the PHP error log for debugging:
```
error_log('Email sending failed: ' . error_message);
```

## Customer Experience

### Email Preview
The email includes:
- Professional header with company branding
- Color-coded status badge matching the status type
- Clear, readable layout
- All relevant order information
- Easy-to-scan item table
- Company contact details
- Footer with automatic email disclaimer

### Email Format
- **HTML Email**: Professional formatting with CSS styling
- **Fallback Plain Text**: Alt body for email clients that don't support HTML
- **Responsive Design**: Looks good on desktop and mobile email clients

## Testing

### To Test Email Sending

1. **Ensure Email Configuration is Set**
   - Update `/config/email.php` with valid SMTP credentials
   - Test Gmail: Use an app-specific password if 2FA is enabled

2. **Update Order Status**
   - Go to an order with a customer email
   - Change the status
   - Check the customer's email inbox and spam folder

3. **Check Error Log**
   - If email fails, check PHP error log in `/xampp/php/logs/`
   - Error messages will indicate configuration issues

### Test Email Addresses
```
Test Customer Email: test@example.com
Company Email: rgb.dempsey@gmail.com
```

## Troubleshooting

### Email Not Sending
**Issue**: Emails are not reaching customers

**Solutions**:
1. Verify SMTP credentials in `/config/email.php`
2. Check if PHP error log shows email errors
3. Confirm customer has email in the database
4. Check email spam/junk folder
5. Verify SMTP port (587 for TLS, 465 for SSL)
6. Test with a different email service (Gmail requires app password)

### Configuration Errors
**Issue**: "Email sending failed: SMTP Error"

**Solutions**:
1. Verify Gmail app-specific password (not regular password)
2. Check firewall/antivirus blocking SMTP
3. Enable "Less secure app access" if not using app password
4. Try different SMTP host (e.g., 'mail.your-domain.com')

### Database Errors
**Issue**: Customer email field is empty

**Solutions**:
1. Update customer information with valid email
2. Check that customer record has email address
3. Verify database connection is working

## Security Considerations

### Email Addresses
- Only emails from verified customers in the database are used
- Email addresses are sanitized before use
- No email addresses are exposed in logs

### Content Protection
- All customer-submitted content is HTML-escaped
- Order information comes from verified database records
- No sensitive data in email headers

### SMTP Security
- Uses TLS encryption for SMTP connection
- Password stored in configuration file (not exposed)
- SMTP credentials should not be committed to version control

## Customization

### To Change Email Messages
Edit the `$status_messages` array in the email function:

```php
$status_messages = [
    'pending' => 'Your custom message here',
    'in_progress' => 'Your custom message here',
    // ...
];
```

### To Customize Email Template
Edit the HTML template in the `sendStatusUpdateEmail()` function. The email uses:
- Company name from email config
- Inline CSS for styling
- Responsive design patterns

### To Add Additional Recipients
Modify the email function to add CC or BCC:

```php
$mail->addCC('manager@example.com');
$mail->addBCC('archive@example.com');
```

## Performance

### Email Sending Impact
- **Synchronous**: Email sends during request (may add 1-2 seconds)
- **No Blocking**: If email fails, status update still completes
- **Graceful Degradation**: System works even if email service is down

### Optimization
For high volume orders, consider:
- Implementing a queue system for emails
- Using background jobs for email sending
- Caching email configuration

## Support & Maintenance

### Regular Checks
- Monitor SMTP connection reliability
- Review error logs weekly
- Test email sending monthly
- Update SMTP credentials as needed

### Maintenance Tasks
- Keep PHPMailer library updated
- Review and update email templates annually
- Audit customer email addresses for validity
- Test failover procedures

## Related Features
- [Payroll Slip Feature](PAYROLL_SLIP_FEATURE.md) - Print-ready payroll documents
- [Transaction Management](DATABASE_SCHEMA_TRANSACTIONS.md) - Comprehensive transaction logging
