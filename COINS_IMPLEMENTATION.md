# Coin earning

Coin earnings are recorded per verified bill in the `coins` ledger. Each `EARN`
row stores the signed amount and the user's balance after that entry. A unique
bill/type constraint and the `bills.coins_paid` flag make bill processing
idempotent.

## Rules

- Coins earned = `floor((CGST + SGST + IGST) * COINS_EARN_RATE)`.
- A bill earns at most `COINS_PER_BILL_LIMIT` coins.
- A user earns at most `COINS_USER_MONTHLY_LIMIT` coins per IST calendar month.
- All users together earn at most `COINS_SHARED_MONTHLY_LIMIT` coins per IST
  calendar month. The locked `coin_monthly_budgets` row serializes this shared
  cap across simultaneous bill scans.
- The seller GSTIN must be valid. A bill with GST above
  `COINS_LARGE_BILL_GST_THRESHOLD` is held for review; it earns only after it is
  approved (`is_process = 0`).
- Verified bills that earn zero due to the monthly caps are still marked as
  processed and are not credited in a later month.
- Coins do not expire. `COINS_PER_RUPEE` is the configured redemption rate.

Default settings are in `config/coins.php` and documented in `.env.example`.
Run `php artisan migrate` to create the earning ledger and shared monthly
budget table. User coin balance and monthly fields use the existing users table
columns.

`billFileUpload` calls `coins_count` after bill extraction. `POST /api/get-coins`
only returns the user's saved coin balance and counters; it does not calculate
earnings, process bills, or modify coin data.
