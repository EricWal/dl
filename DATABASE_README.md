# Card Database Setup

This setup creates a MySQL database to store card information from the cards folder.

## Database Structure

- **Table Name:** cards
- **Columns:**
  - `id` - Auto-incrementing primary key
  - `card_id` - Unique card identifier (extracted from filename)
  - `date` - Date when card was added (today: 2026-05-18)
  - `obtainable` - Boolean flag indicating if card is obtainable (default: TRUE)

## Setup Instructions

### Step 1: Create the Database
Run the SQL script to create the database and table:

```bash
mysql -u root -p < database.sql
```

Or manually execute the `database.sql` file in your MySQL client.

### Step 2: Configure Environment Variables
Copy `.env.example` to `.env` and update your credentials:

```bash
cp .env.example .env
```

Edit `.env` and update:
- `DB_HOST` - MySQL host (default: localhost)
- `DB_USER` - Your MySQL username (default: root)
- `DB_PASSWORD` - Your MySQL password
- `DB_NAME` - Database name (default: cards_db)

**Important:** `.env` is in `.gitignore` and will NOT be uploaded to git for security.

### Step 3: Run the Insertion Script
Run the PHP script to insert all cards from the cards folder:

```bash
php insert_cards.php
```

This script will:
- Scan all PNG files in the cards folder
- Extract card IDs from filenames (number before any space or #)
- Insert each card with today's date and obtainable=true
- Skip duplicate entries
- Display a summary of inserted/skipped cards

## File Naming Convention

The script expects filenames like:
- `10000.png` → card_id = 10000
- `10001 #300707.png` → card_id = 10001
- `10002.png` → card_id = 10002

The script extracts the leading number from each filename as the card_id.

## Example Usage

After setup, you can query the database:

```sql
-- Get all obtainable cards
SELECT * FROM cards WHERE obtainable = TRUE;

-- Get specific card
SELECT * FROM cards WHERE card_id = 10000;

-- Count total cards
SELECT COUNT(*) FROM cards;

-- Get cards added on a specific date
SELECT * FROM cards WHERE date = '2026-05-18';
```

## Notes

- Each card_id can only be inserted once (unique constraint)
- The date field is automatically set to today (2026-05-18)
- The obtainable field can be updated later if needed
- Duplicate inserts will be skipped with a summary
- **SECURITY:** The `.env` file contains sensitive credentials and is excluded from git. Never commit it!
- `.env.example` can be committed to show the required environment variables

