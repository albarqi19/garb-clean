# 🔧 Database Connection Troubleshooting Guide

## Problem Description
The Laravel application on Railway cannot connect to the Supabase PostgreSQL database, showing the error:
```
SQLSTATE[08006] [7] connection to server at "db.uigpeewpideelgckkytx.supabase.co" failed: Network is unreachable
```

## 🔍 Step-by-Step Troubleshooting

### 1. Verify Railway Environment Variables
In your Railway dashboard:
1. Go to your project → Variables
2. Ensure these variables are set correctly:
```
DB_CONNECTION=pgsql
DB_HOST=db.uigpeewpideelgckkytx.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=132648970530996778
DB_SSLMODE=require
```

### 2. Test Database Connection
Run this command in Railway SSH:
```bash
php artisan db:test-connection
```

### 3. Verify Supabase Database Status
1. Go to your Supabase dashboard
2. Check if the database is running
3. Verify the connection details in Settings → Database

### 4. Check Network Connectivity
In Railway SSH, test if you can reach Supabase:
```bash
# Test DNS resolution
nslookup db.uigpeewpideelgckkytx.supabase.co

# Test port connectivity
nc -zv db.uigpeewpideelgckkytx.supabase.co 5432

# Test with curl (if available)
curl -v telnet://db.uigpeewpideelgckkytx.supabase.co:5432
```

### 5. Alternative Connection String
Try using the full PostgreSQL URL format in Railway variables:
```
DATABASE_URL=postgresql://postgres:132648970530996778@db.uigpeewpideelgckkytx.supabase.co:5432/postgres?sslmode=require
```

### 6. SSL Configuration Options
If SSL is causing issues, try these alternatives in Railway variables:

**Option 1: Require SSL**
```
DB_SSLMODE=require
```

**Option 2: Prefer SSL**
```
DB_SSLMODE=prefer
```

**Option 3: Disable SSL (not recommended for production)**
```
DB_SSLMODE=disable
```

### 7. Check Supabase IP Allowlist
1. In Supabase dashboard, go to Settings → Database
2. Check if there are any IP restrictions
3. Railway uses dynamic IPs, so you might need to allow all IPs (0.0.0.0/0)

### 8. Regional Issues
- Supabase instance is in a specific region
- Railway deployment might be in a different region
- Try creating a new Supabase project in a region closer to Railway

### 9. Alternative: Railway PostgreSQL
Consider using Railway's built-in PostgreSQL instead:
1. In Railway dashboard, add PostgreSQL service
2. Railway will automatically set DATABASE_URL
3. Update your Laravel configuration to use Railway's database

## 🚀 Quick Fixes to Try

### Fix 1: Clear Config Cache
```bash
php artisan config:clear
php artisan config:cache
```

### Fix 2: Set DATABASE_URL
Instead of individual DB_* variables, use:
```
DATABASE_URL=postgresql://postgres:132648970530996778@db.uigpeewpideelgckkytx.supabase.co:5432/postgres?sslmode=require
```

### Fix 3: Retry with Different SSL Modes
Try these in order:
1. `DB_SSLMODE=require`
2. `DB_SSLMODE=prefer`
3. `DB_SSLMODE=allow`

## 📞 Next Steps
1. Try the troubleshooting steps above
2. Run the database test command
3. Check Railway logs for more details
4. Consider switching to Railway PostgreSQL if Supabase continues to have connectivity issues

## 💡 Best Practices
- Always use SSL in production
- Keep database credentials secure
- Monitor connection pools and timeouts
- Consider using connection pooling for better performance
