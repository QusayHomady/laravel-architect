# Laravel Architect — Action / Service / DTO / Repository

باكج بسيط ومحترف من تطوير **Qusai Homadi** يضيف لك أوامر Artisan جاهزة لتوليد بنية برمجية متكاملة ونظيفة:
**Repository + Interface**, **Service**, **DTO**, **Action** بضغطة زر، بدل ما تكتبها يدوي كل مرة.

---

## طريقة التثبيت (بما إنها باكج محلي وليست على Packagist حالياً)

### 1) انسخ مجلد الباكج داخل مشروع Laravel

انسخ مجلد `laravel-generators` كامل إلى داخل مشروعك، مثلاً في:

```
your-laravel-project/packages/qusaihomadi/laravel-architect
```

### 2) عرّف الباكج في composer.json الخاص بمشروعك

أضف قسم `repositories` وقسم `require`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/qusaihomadi/laravel-architect"
    }
],
"require": {
    "qusaihomadi/laravel-architect": "*"
}
```

### 3) ثبّت الباكج

```bash
composer update qusaihomadi/laravel-architect
```

> إذا تستخدم Laravel 11+ مع Package Discovery، ما تحتاج تسجل الـ Service Provider يدوي.
> إذا ما اشتغل التسجيل التلقائي، ضيفه يدوي في `bootstrap/providers.php` (Laravel 11) أو `config/app.php` (Laravel 10 وأقل):

```php
QusaiHomadi\LaravelArchitect\GeneratorsServiceProvider::class,
```

### 4) (اختياري) انشر ملفات الـ stubs عشان تعدل عليها حسب ستايلك

```bash
php artisan vendor:publish --tag=laravel-architect-stubs
```

بعدها راح تلقاها في: `stubs/vendor/laravel-architect/*.stub`

### 5) (اختياري) انشر ملف الإعدادات للتحكم بالمسارات والـ namespaces

```bash
php artisan vendor:publish --tag=laravel-architect-config
```

بعدها راح تلقاه في: `config/laravel-architect.php` — عدّل فيه أي namespace أو مسار مجلد يناسب هيكلة مشروعك.

---

## الأوامر المتوفرة

كل أمر يقبل الاسم بصيغتين: `User` أو `UserRepository` (ونفس الشي للباقي)، يعني تقدر تكتب بالضبط:

```bash
php artisan make:repository UserRepository
```

### 1) توليد كل طبقة لحالها

```bash
php artisan make:repository User
php artisan make:service User
php artisan make:dto User
php artisan make:action CreateUserAction --service=UserService --dto=UserDTO
```

### 2) توليد الكل مع بعض — عندك طريقتين

**الطريقة أ: أضف `--all` على أي أمر من الأربعة** وهو تلقائياً يكمل توليد باقي الطبقات لنفس الكيان:

```bash
php artisan make:repository UserRepository --all
# يولد: Repository + Interface + Service + DTO + Action لكيان User بأمر واحد فقط

php artisan make:service UserService --all
php artisan make:dto UserDTO --all
php artisan make:action CreateUserAction --all
```

**الطريقة ب: أمر مختصر مخصص للوحدة الكاملة**

```bash
php artisan make:module User
```

كلا الطريقتين تنتج نفس الملفات بالضبط — استخدم اللي يريحك أكثر.

### خيار `--force`

لو الملف موجود مسبقاً، الأمر بشكل افتراضي يتجاهله ويطبع تحذير حتى ما يمسح شغلك بالغلط.
لو تبي تستبدل الملفات الموجودة:

```bash
php artisan make:repository User --all --force
```

## الملفات الناتجة

```
app/
├── Repositories/
│   ├── Contracts/
│   │   └── UserRepositoryInterface.php
│   └── UserRepository.php
├── Services/
│   └── UserService.php
├── DTOs/
│   └── UserDTO.php
└── Actions/
    └── CreateUserAction.php
```

## خطوة مهمة بعد التوليد: ربط الـ Interface بالتطبيق الفعلي

في `app/Providers/AppServiceProvider.php` (أو Provider خاص بالريبوزيتوريز):

```php
public function register(): void
{
    $this->app->bind(
        \App\Repositories\Contracts\UserRepositoryInterface::class,
        \App\Repositories\UserRepository::class
    );
}
```

## التخصيص

كل الملفات المولّدة هي نقطة بداية بسيطة (CRUD أساسي). عدّل الـ stubs الموجودة في مجلد `stubs/`
(أو بعد النشر في `stubs/vendor/laravel-architect/`) لتضيف منطقك الخاص، Validation، Events، إلخ.

---

## Dynamic DTO Generation (توليد الـ DTO التلقائي من الموديل)

تم دمج محرك توليد DTO متقدم وذكي جداً! عند تشغيل أمر توليد الـ DTO:
```bash
php artisan make:dto User
```
سيبحث المولد تلقائياً عن موديل `User` في مشروعك، ويقوم بـ:
1. قراءة الحقول المعرفة في مصفوفة `$fillable` داخل الموديل.
2. استنتاج أنواع الحقول (Types) تلقائياً بناءً على مصفوفة الـ `$casts` (مثل `boolean` -> `bool`, `datetime` -> `Carbon`, إلخ).
3. تحويل أسماء الحقول لـ `camelCase` كخصائص للقراءة فقط (Readonly Properties).
4. إنشاء دوال مساعدة تلقائياً للتحويل والتوليد:
   - `fromModel()`
   - `fromArray()`
   - `fromRequest()`
   - `toArray()`

> [!NOTE]
> إذا لم يتم العثور على الموديل في مشروعك، لا تقلق! سيقوم المولد تلقائياً بالتراجع (Fallback) وإنشاء كلاس DTO افتراضي فارغ وبسيط لتتمكن من تعبئته بنفسك.

### خيارات التحكم بالـ DTO في ملف الإعدادات:
في ملف `config/laravel-architect.php` يمكنك تفعيل أو إلغاء تفعيل الميزات الإضافية للـ DTO:
```php
    'dto' => [
        'namespace' => 'App\\DTOs',
        'path' => app_path('DTOs'),
        'readonly' => true, // استخدام readonly للمتغيرات (PHP 8.1+)
        'generate_from_model' => true, // توليد دالة fromModel
        'generate_from_array' => true, // توليد دالة fromArray
        'generate_from_request' => true, // توليد دالة fromRequest
        'generate_to_array' => true, // توليد دالة toArray
    ],
```

