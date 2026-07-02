# SIW Coins Calculation System - Implementation Summary

## Overview
The `getCoins` function has been successfully implemented to calculate and manage SIW Coins for users based on verified bills and GST amounts.

## Key Features Implemented

### 1. **Coin Calculation Formula**
```
Coins = Verified GST × Visibility Multiplier × Profit Control Factor × 10 × Tier Multiplier
Default: Verified GST × 1.5 × 0.3 × 10 × Tier Multiplier
```

### 2. **Tier System**
Based on verified bill scans:
- **Bronze** (New User): 1.0x multiplier
- **Silver** (10+ verified scans): 1.1x multiplier
- **Gold** (50+ scans within 30 days): 1.2x multiplier
- **Platinum** (200+ verified scans): 1.5x multiplier

### 3. **Anti-Fraud Controls**
- **Daily Limit**: 500 coins per day (resets at midnight)
- **Monthly Limit**: 5,000 coins per month (resets on 1st of month)
- **Duplicate Bills**: Rejected automatically (via is_process flag)
- **GSTIN Validation**: Mandatory for verified bills

### 4. **Verification Criteria**
A bill is considered **verified** when:
- `is_process = 0` in the bills table
- This means the bill has all required fields and valid GSTIN

### 5. **Database Changes**
A new migration `2026_07_02_000000_add_coins_to_users_table` was created to add:
- `coins` (int): Total coins earned by the user
- `daily_coin_count` (int): Coins earned today
- `daily_coin_reset_date` (date): Last reset date for daily counter
- `monthly_coin_count` (int): Coins earned this month
- `monthly_coin_reset_date` (date): Last reset date for monthly counter
- `tier` (string): Current user tier

## API Endpoint

### POST `/api/get-coins`

**Request:**
```json
{
  "userid": 123
}
```

**Response:**
```json
{
  "message": "Coins calculated successfully",
  "data": {
    "user_id": 123,
    "verified_scans": 45,
    "total_verified_gst": 2500.00,
    "tier": "Silver",
    "tier_multiplier": 1.1,
    "calculated_coins": 10350,
    "coins_after_daily_limit": 500,
    "coins_earned_this_transaction": 500,
    "total_coins": 15850,
    "daily_coin_count": 500,
    "daily_limit": 500,
    "monthly_coin_count": 3500,
    "monthly_limit": 5000,
    "remaining_daily_coins": 0,
    "remaining_monthly_coins": 1500
  },
  "status": 200
}
```

## Implementation Details

### Function Workflow:
1. **Validate User**: Check if user exists
2. **Get Verified Bills**: Retrieve all bills where `is_process = 0`
3. **Calculate GST**: Sum CGST + SGST + IGST from verified bills
4. **Determine Tier**: Based on verified scan count
5. **Calculate Coins**: Apply formula with tier multiplier
6. **Check Limits**: Apply daily and monthly constraints
7. **Update Database**: Save coins and tier information
8. **Return Response**: Send detailed coin information

### Automatic Features:
- **Daily Counter Reset**: Automatically resets when date changes
- **Monthly Counter Reset**: Automatically resets on new month
- **Tier Auto-Update**: Tier is calculated and updated each call
- **Scan Count Update**: `scan_bill` field updated with verified scan count
- **Tax Tracking**: `tax_identified` field updated with total verified GST

## Business Rules Enforced

1. **Only verified bills count**: Bills with `is_process = 0` only
2. **Tier progression**: Users automatically move to higher tiers based on scan count
3. **30-day period**: Gold tier checks for 50+ bills within last 30 days
4. **Daily limit enforcement**: Cannot exceed 500 coins per day
5. **Monthly limit enforcement**: Cannot exceed 5,000 coins per month
6. **GSTIN validation**: Pre-validated at bill creation (is_process flag)

## Example Calculation

For a user with:
- 45 verified bills
- Total verified GST: ₹2500
- Tier: Silver (1.1x multiplier)

Calculation:
```
2500 × 1.5 × 0.3 × 10 × 1.1 = 12,375 coins
Daily limit applied: min(12,375, 500 - 0) = 500 coins earned today
Monthly total updated: 500 coins added to monthly count
```

## Files Modified/Created

1. **Created**: `database/migrations/2026_07_02_000000_add_coins_to_users_table.php`
2. **Modified**: `database/migrations/2026_06_07_000000_create_monthly_bills_pdf_table.php` (Fixed for existing table)
3. **Modified**: `app/Http/Controllers/apiController.php` (Added complete getCoins function)

## Testing the Function

To test the function via API:
```bash
POST /api/get-coins
{
  "userid": 1
}
```

The function will:
- Calculate coins based on verified bills
- Update the user's coin balance
- Track daily and monthly limits
- Return detailed coin information
