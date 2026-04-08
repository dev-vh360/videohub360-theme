# Membership Checkout Account Requirement

## Problem Summary

The membership purchase flow was not safe for guest checkout. The membership grant logic in `class-vh360-membership-woocommerce.php` depends on `$order->get_user_id()`, and both `process_order()` and `revoke_order_memberships()` return immediately if the order has no WordPress user attached.

This created a critical gap where:
- WooCommerce could accept an order
- Payment could succeed
- Order could move to processing/completed status
- BUT no membership would be granted because there was no user_id

## Solution Implemented

The fix ensures that **membership products always require account creation at checkout** by modifying `includes/woocommerce-integration.php`.

### Implementation Details

#### 1. Membership Product Detection Helper

Added `vh360_wc_cart_contains_membership()` function that:
- Checks if WooCommerce cart exists
- Iterates through all cart items
- Uses `VH360_Membership_Plans::get_product_membership_mapping($product_id)` as the source of truth
- Returns `true` if any product has a non-empty `plan_key` in its mapping

#### 2. Force Account Creation Filters

Added three WooCommerce filters at priority 999 to ensure membership carts require accounts:

**a) Force registration required:**
```php
add_filter('woocommerce_checkout_registration_required', ...)
```
Returns `true` when cart contains membership products.

**b) Disable guest checkout:**
```php
add_filter('pre_option_woocommerce_enable_guest_checkout', ...)
```
Returns `'no'` when cart contains membership products, effectively disabling guest checkout.

**c) Pre-check account creation:**
```php
add_filter('woocommerce_create_account_default_checked', ...)
```
Returns `true` when cart contains membership products, automatically checking the account creation checkbox.

#### 3. Hard Validation Guard

Added final validation in `woocommerce_checkout_process` hook at priority 999:
- Checks if cart contains membership products
- If yes, verifies user is logged in OR account is being created
- If neither condition is met, adds error notice and prevents checkout

This is a safety net that should never trigger due to the filters above, but guards against edge cases and plugin conflicts.

## Testing Scenarios

### 1. Logged-out user, membership product only
**Expected behavior:**
- Account creation is required and enforced
- Account creation checkbox is pre-checked
- Guest checkout is disabled
- Order is attached to the newly created WordPress user
- Membership is granted when order becomes processing/completed

### 2. Logged-out user, mixed cart (membership + non-membership)
**Expected behavior:**
- Account creation is still required (entire cart treated as membership cart)
- Membership is granted correctly to the new user
- Non-membership products are also delivered

### 3. Logged-in user, membership product
**Expected behavior:**
- No guest logic interferes
- Checkout proceeds normally
- Membership is granted to the logged-in user account
- All existing account handlers work correctly

### 4. Guest user, non-membership product only
**Expected behavior:**
- Normal site checkout behavior remains unchanged
- Guest checkout is allowed (if enabled in WooCommerce settings)
- No forced account creation

### 5. Refunded/cancelled membership order
**Expected behavior:**
- Membership revocation flow still works
- Order has a valid user_id (because account was created at checkout)
- `revoke_order_memberships()` can properly cancel the membership

## Architecture Alignment

This implementation follows the existing VH360 membership system architecture:

1. **No changes to membership grant engine**: The core processing in `class-vh360-membership-woocommerce.php` remains intact and unchanged.

2. **Checkout-level enforcement**: Account requirement is enforced at checkout, not post-purchase, matching how the grant engine expects orders to arrive.

3. **Respects existing account handlers**: All existing handlers in `woocommerce-integration.php` continue to work:
   - First/last name validation
   - Custom registration field storage
   - Display name generation
   - Role alignment via `woocommerce_created_customer`

4. **Single source of truth**: Uses `VH360_Membership_Plans::get_product_membership_mapping()` to determine if a product is a membership product.

## Policy

The enforced rule is simple and clear:
- **Membership purchase = account required**
- **Non-membership purchase = normal WooCommerce/site behavior**

This matches the user-based architecture of the VH360 membership system and prevents the payment-without-access failure path.

## Files Modified

- `includes/woocommerce-integration.php`:
  - Added `vh360_wc_cart_contains_membership()` helper
  - Added three WooCommerce filters to force account creation for membership carts
  - Added hard validation guard in checkout process

## Files NOT Modified

- `bundled-plugins/videohub360-memberships/includes/class-vh360-membership-woocommerce.php`: No changes needed - the grant/revoke engine works correctly once accounts are guaranteed to exist.

## Related Hooks & Filters

### New Filters Applied
- `woocommerce_checkout_registration_required` (priority 999)
- `pre_option_woocommerce_enable_guest_checkout` (priority 999)
- `woocommerce_create_account_default_checked` (priority 999)

### New Actions Applied
- `woocommerce_checkout_process` (priority 999) - validation guard

### Existing Hooks Preserved
- `woocommerce_checkout_fields` (priority 20)
- `woocommerce_checkout_process` (priority 20) - name validation
- `woocommerce_checkout_update_user_meta` (priority 20)
- `woocommerce_created_customer` (priority 20)

## Future Considerations

1. **UI Enhancement**: Consider adding a notice on checkout page explaining why account creation is required when membership products are in cart.

2. **Admin Notice**: Could add an admin notice on the product edit screen when a membership mapping is added, explaining the account requirement.

3. **Cart Notice**: Could add a cart notice when membership products are added, informing users they'll need to create an account.

4. **Documentation**: Update user-facing documentation to explain the account requirement for membership purchases.
