# 🐘 إعداد قاعدة البيانات PostgreSQL مع Supabase

## 📋 خطوات الإعداد

### 1. إنشاء مشروع Supabase
1. اذهب إلى: https://supabase.com
2. **Create new project**
3. **اسم المشروع**: `garb-clean-db`
4. **كلمة مرور قاعدة البيانات**: (احفظها جيداً)
5. **المنطقة**: Middle East (أو أقرب منطقة)

### 2. الحصول على معلومات الاتصال
بعد إنشاء المشروع:
1. اذهب إلى **Settings** → **Database**
2. انسخ **Connection String** (URI mode)
3. سيكون بالشكل:
```
postgresql://postgres:[YOUR-PASSWORD]@db.[PROJECT-REF].supabase.co:5432/postgres
```

### 3. تحديث .env في المشروع
```env
DB_CONNECTION=pgsql
DB_HOST=db.[PROJECT-REF].supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=[YOUR-PASSWORD]
```

### 4. تحديث config/database.php
تأكد من أن إعدادات PostgreSQL صحيحة.

### 5. تثبيت PostgreSQL Driver (إذا لزم الأمر)
```bash
composer require doctrine/dbal
```

### 6. تشغيل Migrations
```bash
php artisan migrate
```

### 7. إنشاء مستخدم Filament Admin
```bash
php artisan make:filament-user
```

## 🎯 الهدف
- قاعدة بيانات PostgreSQL جاهزة
- تشغيل الـ migrations الأساسية
- Filament Admin Panel يعمل
- استعداد لنقل Models من المشروع القديم

## 📝 ملاحظات
- Supabase مجاني حتى 500MB و 2GB نقل بيانات
- أسرع بكثير من ngrok + MySQL المحلي
- جاهز للإنتاج مباشرة
- نسخ احتياطي تلقائي
