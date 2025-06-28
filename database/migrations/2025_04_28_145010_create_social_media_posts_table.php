<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('social_media_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان المنشور');
            $table->text('content')->nullable()->comment('محتوى المنشور');
            $table->string('post_type')->comment('نوع المنشور: مادة إثرائية، تلاوة قرآنية، إنجازات، حملة، إلخ');
            $table->string('post_link')->nullable()->comment('رابط المنشور بعد النشر');
            $table->date('publish_date')->comment('تاريخ النشر');
            $table->string('publish_date_hijri')->nullable()->comment('تاريخ النشر بالهجري');
            $table->integer('views_count')->default(0)->comment('عدد المشاهدات');
            $table->integer('likes_count')->default(0)->comment('عدد الإعجابات');
            $table->integer('comments_count')->default(0)->comment('عدد التعليقات');
            $table->integer('shares_count')->default(0)->comment('عدد المشاركات');
            $table->integer('saves_count')->default(0)->comment('عدد الحفظ');
            $table->decimal('interaction_rate', 8, 2)->default(0)->comment('معدل التفاعل بالنسبة المئوية');
            
            // منصات النشر
            $table->boolean('twitter')->default(false)->comment('تم النشر على تويتر');
            $table->boolean('instagram')->default(false)->comment('تم النشر على انستغرام');
            $table->boolean('facebook')->default(false)->comment('تم النشر على فيسبوك');
            $table->boolean('telegram')->default(false)->comment('تم النشر على تيليجرام');
            $table->boolean('snapchat')->default(false)->comment('تم النشر على سناب شات');
            $table->boolean('whatsapp')->default(false)->comment('تم النشر على واتساب');
            $table->boolean('youtube')->default(false)->comment('تم النشر على يوتيوب');

            // حالة المنشور
            $table->enum('status', ['مجدول', 'منشور', 'ملغي', 'مؤجل'])->default('مجدول')->comment('حالة المنشور');
            
            // مؤشرات الأداء (KPIs) والأهداف
            $table->integer('target_interaction')->nullable()->comment('الهدف المستهدف للتفاعل');
            $table->decimal('achievement_percentage', 8, 2)->default(0)->comment('نسبة تحقيق الهدف');
            
            // الربط بالمستخدمين والأقسام والحملات
            $table->foreignId('created_by')->constrained('users')->comment('منشئ المنشور');
            $table->foreignId('published_by')->nullable()->constrained('users')->comment('ناشر المنشور');
            $table->foreignId('marketing_activity_id')->nullable()->constrained()->comment('النشاط التسويقي المرتبط');
            $table->foreignId('marketing_kpi_id')->nullable()->constrained()->comment('مؤشر الأداء المرتبط');
            $table->string('target_audience')->nullable()->comment('الجمهور المستهدف');
            
            // حقول إضافية للمتابعة
            $table->text('notes')->nullable()->comment('ملاحظات');
            $table->text('marketing_analysis')->nullable()->comment('تحليل تسويقي للمنشور');
            $table->timestamps();
            $table->softDeletes();
            
            // مؤشرات للبحث
            $table->index('post_type');
            $table->index('publish_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_media_posts');
    }
};
