# 📋 خطة نقل مشروع GARB - مرحلية وآمنة

## 🎯 الهدف
نقل المشروع من Laravel 12 + MySQL إلى Laravel 10 + PostgreSQL على Railway

## 📊 تحليل المشروع الأصلي
- **Laravel**: 12.x
- **Filament**: 3.3
- **Models**: 60+ نموذج
- **Migrations**: 80+ ملف هجرة
- **Dependencies**: filament, spatie-permission, livewire, doctrine/dbal

## 🔄 خطوات النقل (بالتدريج)

### **المرحلة 1: إعداد الأساسيات** ✅
- [x] إنشاء Laravel 10 نظيف
- [x] رفع على GitHub
- [x] نشر على Railway بنجاح

### **المرحلة 2: ترقية Laravel وإضافة Dependencies**
- [ ] ترقية Laravel 10 → Laravel 11
- [ ] تثبيت Filament 3.2+ (متوافق مع Laravel 11)
- [ ] تثبيت Spatie Permissions
- [ ] تثبيت باقي Dependencies الأساسية
- [ ] اختبار النشر على Railway

### **المرحلة 3: نقل Database Structure**
- [ ] نقل Migrations الأساسية (Users, Mosques, QuranCircles)
- [ ] تشغيل php artisan migrate
- [ ] اختبار قاعدة البيانات
- [ ] نقل باقي Migrations تدريجياً

### **المرحلة 4: نقل Models**
- [ ] نقل Models الأساسية (User, Mosque, QuranCircle)
- [ ] نقل Models المرتبطة (Teacher, Student)
- [ ] نقل باقي Models تدريجياً
- [ ] اختبار العلاقات

### **المرحلة 5: نقل Filament Admin Panel**
- [ ] تثبيت Filament Admin Panel
- [ ] نقل Filament Resources الأساسية
- [ ] نقل Forms وTables
- [ ] نقل Widgets
- [ ] نقل Custom Components

### **المرحلة 6: إعداد قاعدة البيانات الإنتاجية**
- [ ] إعداد Supabase PostgreSQL
- [ ] ربط المشروع مع Supabase
- [ ] نقل البيانات من MySQL إلى PostgreSQL
- [ ] اختبار البيانات والأداء

### **المرحلة 7: الاختبار النهائي والتحسين**
- [ ] اختبار جميع الوظائف
- [ ] تحسين الأداء
- [ ] إعداد النسخ الاحتياطي التلقائي
- [ ] توثيق التغييرات

## 🔧 أدوات النقل
- **Git**: لحفظ كل مرحلة
- **Railway**: للنشر المباشر
- **Supabase**: قاعدة البيانات النهائية
- **PowerShell**: سكريبتات النقل

## ⚠️ نقاط مهمة
1. **اختبار كل مرحلة** قبل الانتقال للتالية
2. **عمل commit** بعد كل مرحلة ناجحة
3. **إبقاء المشروع القديم** كنسخة احتياطية
4. **تشغيل Migration فقط** عند التأكد من النماذج
5. **اختبار Railway** بعد كل تغيير كبير

## 📈 Timeline المتوقع
- **المرحلة 2-3**: يوم واحد
- **المرحلة 4**: يوم واحد  
- **المرحلة 5**: 2-3 أيام
- **المرحلة 6-7**: يوم واحد

**إجمالي**: 5-6 أيام عمل
