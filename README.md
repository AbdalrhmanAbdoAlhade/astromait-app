# AstroMait Marketplace API

منصة **AstroMait** هي واجهة API خلفية لمنصة تجارة وخدمات متعددة البائعين. تجمع المنصة بين بيع المنتجات، حجز الخدمات، المزادات، الشهادات القابلة للتحقق، السلة والطلبات، الكوبونات، الشحن، المدفوعات، والمحتوى التحريري ضمن تطبيق Laravel منظم وقابل للتوسعة.

> المشروع في وضعه الحالي هو **Backend API**؛ لا يحتوي المستودع على واجهة متجر Frontend مكتملة. يمكن بناء واجهة React أو تطبيق جوال فوق مسارات API الموثقة في هذا الملف.

## الحالة الحالية

| المجال | الحالة |
|---|---|
| Laravel API | متاح عبر `/api/v1` |
| المصادقة | تسجيل، دخول، المستخدم الحالي، تسجيل خروج عبر Laravel Sanctum |
| الأدوار | `admin` و`vendor` و`user` عبر Spatie Permission |
| المنتجات والخدمات | مسارات عامة وإدارة البائع للمنتجات والخدمات |
| الطلبات والسلة | مهيأة ضمن طبقة API والخدمات |
| المزادات والعروض | مسارات عامة ومسارات للمزايدة وإدارة مزادات البائع |
| الشهادات | إصدار الشهادة والتحقق منها برقم الشهادة |
| المدفوعات والشحن | خدمات وإعدادات قابلة للربط بمزودي الخدمة |
| الاختبارات | 4 اختبارات ناجحة، تشمل دورة المصادقة الأساسية |
| واجهة المستخدم | غير مضمّنة في المستودع حاليًا |

## المزايا الرئيسية

تدعم المنصة ثلاث فئات رئيسية من المستخدمين. يستطيع المستخدم تصفح المنتجات والخدمات، إدارة عناوينه وسلته، تنفيذ الطلبات، حجز الخدمات، المزايدة في المزادات، وتطبيق الكوبونات. يستطيع البائع إدارة منتجاته وخدماته ومزاداتِه وكوبوناته وطلبات متجره بعد اعتماد الحساب. أما المدير فيدير البائعين والمنتجات والخدمات والتصنيفات والبنرات والمقالات والشهادات.

وتعتمد المصادقة على **Laravel Sanctum** لإصدار Bearer Tokens، بينما تُدار الأدوار والصلاحيات بواسطة **Spatie Laravel Permission**. جرى فصل الكنترولرات إلى نطاقات `Public` و`User` و`Vendor` و`Admin` حتى تبقى حدود الصلاحيات واضحة وقابلة للصيانة.

## المتطلبات

| المتطلب | الإصدار أو القيمة المقترحة |
|---|---|
| PHP | `^8.2` |
| Laravel | `^13.0` |
| Composer | الإصدار 2 أو أحدث |
| Node.js | `22` أو أحدث عند استخدام Vite |
| قاعدة البيانات | MySQL للإنتاج، أو SQLite للتجربة والاختبارات |
| إضافات PHP | `mbstring`, `xml`, `curl`, `pdo`, وامتداد قاعدة البيانات المختار |

## التثبيت والتشغيل

استنسخ المشروع ثم ادخل إلى مجلده:

```bash
git clone https://github.com/AbdalrhmanAbdoAlhade/astromait-app.git
cd astromait-app
```

ثبّت اعتماديات PHP وأنشئ ملف البيئة والمفتاح السري:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

للتشغيل السريع باستخدام SQLite، أنشئ ملف قاعدة البيانات وعدّل القيم التالية في `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/astromait-app/database/database.sqlite
```

بعد ذلك نفّذ الترحيلات والبيانات الابتدائية:

```bash
touch database/database.sqlite
php artisan migrate --seed
```

يشغّل `DatabaseSeeder` كلًا من `RoleSeeder` و`AdminUserSeeder`. بيانات المدير الافتراضية مخصصة للبيئة المحلية فقط، ويجب تغييرها أو تعطيلها قبل أي نشر حقيقي.

شغّل خادم التطوير:

```bash
php artisan serve
```

يصبح عنوان API المحلي عادةً:

```text
http://127.0.0.1:8000/api/v1
```

## إعدادات البيئة المهمة

| المتغير | الغرض | القيمة الافتراضية |
|---|---|---|
| `APP_URL` | عنوان التطبيق | `http://localhost:8000` |
| `DB_CONNECTION` | محرك قاعدة البيانات | `mysql` |
| `PAYMENT_GATEWAY` | مزود الدفع الافتراضي | `adfpay` |
| `SHIPPING_PROVIDER` | مزود الشحن الافتراضي | `dhl` |
| `DEFAULT_SHIPPING_COST` | تكلفة الشحن الاحتياطية | `30` |
| `DEFAULT_COMMISSION_RATE` | عمولة المنصة الاحتياطية | `10` |
| `EDFAPAY_*` | بيانات ربط EdfaPay | يجب ضبطها في بيئة التشغيل |
| `DHL_*` | بيانات ربط DHL | يجب ضبطها في بيئة التشغيل |

لا تضع مفاتيح الدفع أو الشحن أو ملف `.env` داخل Git. استخدم مدير أسرار في بيئة الإنتاج، واضبط `APP_DEBUG=false` قبل النشر.

## المصادقة

### التسجيل

```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "Astromait User",
  "email": "user@example.com",
  "phone": "+966500000000",
  "password": "password123",
  "password_confirmation": "password123"
}
```

يعيد الطلب `201 Created` مع بيانات المستخدم وBearer Token.

### تسجيل الدخول

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}
```

مرّر التوكن الناتج في الطلبات المحمية:

```http
Authorization: Bearer YOUR_TOKEN
```

### المستخدم الحالي وتسجيل الخروج

```http
GET  /api/v1/auth/me
POST /api/v1/auth/logout
```

## خريطة API المختصرة

| المجموعة | المسارات الأساسية |
|---|---|
| المصادقة | `auth/register`, `auth/login`, `auth/me`, `auth/logout` |
| الكتالوج العام | `categories`, `products`, `services`, `banners`, `articles` |
| المزادات | `auctions`, `auctions/{auction}`, `auctions/{auction}/bid` |
| الشهادات | `certificates/{number}/verify` |
| المستخدم | `cart`, `orders`, `addresses`, `service-bookings`, `coupons/apply` |
| البائع | `vendor/products`, `vendor/services`, `vendor/auctions`, `vendor/coupons`, `vendor/orders`, `vendor/dashboard` |
| المدير | `admin/vendors`, `admin/products`, `admin/services`, `admin/categories`, `admin/banners`, `admin/coupons`, `admin/articles`, `admin/certificates` |
| التكاملات | `payments/webhook` |

يمكن استخراج القائمة الكاملة للمسارات من المشروع عبر:

```bash
php artisan route:list
```

## بنية المشروع

```text
app/
├── Http/Controllers/Api/
│   ├── Admin/       # عمليات الإدارة والاعتماد
│   ├── Public/      # الكتالوج العام والمصادقة والـ webhooks
│   ├── User/        # السلة والطلبات والعناوين والحجوزات
│   └── Vendor/      # أدوات وإدارة البائع
├── Models/          # نماذج Eloquent والعلاقات
└── Services/        # الدفع، الشحن، السلة، المزادات، واعتماد البائع

database/
├── factories/       # Factory للاختبارات
├── migrations/      # مخطط قاعدة البيانات
└── seeders/         # الأدوار والمستخدم الإداري

routes/api.php       # جميع مسارات API تحت الإصدار v1
config/              # إعدادات الدفع والشحن والسوق
resources/           # أصول Laravel وVite المتاحة للمشروع
 tests/              # اختبارات Unit وFeature
```

## الاختبارات والجودة

شغّل جميع الاختبارات عبر Pest:

```bash
php artisan test
```

ولتشغيل اختبار المصادقة وحده:

```bash
php artisan test tests/Feature/AuthTest.php
```

وتتضمن الاختبارات الحالية التسجيل، تسجيل الدخول، قراءة الملف الشخصي، وتسجيل الخروج وإبطال التوكن. قبل الدمج في فرع الإنتاج يُنصح بإضافة اختبارات للطلبات، صلاحيات البائع والمدير، الدفع، webhooks، والمزايدات المتزامنة.

## أوامر التطوير المفيدة

```bash
php artisan migrate:fresh --seed
php artisan route:list
php artisan config:clear
php artisan cache:clear
php artisan storage:link
npm install
npm run dev
npm run build
```

## ملاحظات أمنية وتشغيلية

يجب التحقق من توقيع webhook الخاص ببوابة الدفع قبل تحديث حالة أي طلب في بيئة الإنتاج، كما يجب جعل endpoint الخاص بالـ webhook قابلًا للتكرار الآمن بحيث لا يؤدي تكرار نفس الإشعار إلى تنفيذ التسوية مرتين. ينبغي كذلك ضبط CORS و`SANCTUM_STATEFUL_DOMAINS` بما يتوافق مع نطاق الواجهة الفعلي، وتفعيل HTTPS، وتدوير مفاتيح التكامل، وتغيير بيانات المدير الافتراضية.

توجد بنية الترحيلات كاملة للمجالات الأساسية، لكن المشروع لا يحتوي بعد على واجهة مستخدم، وطبقة التكامل الفعلية مع مزودي الدفع والشحن تحتاج إلى مفاتيح البيئة واختبارات sandbox. هذه النقاط جزء من خطة الإطلاق وليست بدائل عن إعدادات الإنتاج.

## المساهمة

أنشئ فرعًا جديدًا لكل ميزة أو إصلاح، أضف اختبارًا يغطي السلوك الجديد، ثم شغّل `php artisan test` وراجِع `php artisan route:list` قبل فتح Pull Request. يجب ألا تُرفع ملفات `.env` أو مفاتيح الخدمات أو قواعد البيانات المحلية إلى المستودع.

## الترخيص

لم يحدد المستودع ترخيصًا مفتوح المصدر صريحًا حتى الآن. قبل النشر العام، أضف ملف `LICENSE` يوضح حقوق الاستخدام والتوزيع.

## المراجع

[1]: https://laravel.com/docs Laravel Documentation
[2]: https://laravel.com/docs/sanctum Laravel Sanctum Documentation
[3]: https://spatie.be/docs/laravel-permission/v6/introduction Spatie Laravel Permission Documentation
[4]: https://laravel.com/docs/testing Laravel Testing Documentation
[5]: https://vite.dev/guide/ Vite Documentation

## نظام محفظة التاجر والتسويات المالية

يحتوي المشروع الآن على نظام مالي يفصل بين **الرصيد المعلق** الناتج عن الطلبات المدفوعة، و**الرصيد المتاح** بعد اكتمال الطلب والتسوية، و**الرصيد المحجوز** أثناء مراجعة طلب التحويل، و**الرصيد المحوّل** بعد تنفيذ التحويل. كل حركة تُحفظ في `wallet_transactions` مع snapshot للأرصدة بعد الحركة ومفتاح `idempotency_key` لمنع تكرار webhook أو تنفيذ العملية مرتين.

### الجداول المالية

| الجدول | الغرض |
|---|---|
| `vendor_wallets` | رصيد محفظة واحد لكل تاجر، ويضم الأرصدة المعلقة والمتاحة والمحجوزة والمحوّلة وإجماليات المبيعات والعمولة |
| `wallet_transactions` | سجل القيود المالية مع deltas وsnapshot بعد كل حركة ومرجع الطلب أو التحويل |
| `vendor_payout_accounts` | وسائل التحويل الخاصة بالتاجر، مثل الحساب البنكي أو IBAN |
| `settlements` | تسوية مالية لفترة محددة وتاجر محدد |
| `settlement_items` | عناصر الطلب الداخلة في التسوية مع الإجمالي والعمولة والاسترداد والصافي |
| `payout_requests` | طلبات تحويل الأموال من محفظة التاجر إلى وسيلة الدفع المسجلة |

### دورة الأموال

```text
دفع ناجح
  ↓
sale_pending: صافي التاجر في pending_balance
  ↓
اكتمال الطلب وإنشاء التسوية
  ↓
sale_released: نقل الصافي إلى available_balance
  ↓
طلب تحويل التاجر
  ↓
payout_hold: نقل المبلغ إلى held_balance
  ↓
اعتماد الإدارة
  ├── رفض: payout_released وإرجاع المبلغ إلى available_balance
  └── تنفيذ: payout_paid ونقل المبلغ إلى paid_balance
```

تسجيل الدفع مربوط بـ `PaymentGatewayService`، وهو idempotent على مستوى عنصر الطلب. أما العمليات المالية نفسها فتُنفّذ داخل معاملات قاعدة البيانات مع `lockForUpdate`، ولا يُسمح بجعل أي رصيد سالبًا.

### مسارات التاجر

| Method | Endpoint | الوظيفة |
|---|---|---|
| `GET` | `/api/v1/vendor/wallet` | عرض أرصدة المحفظة وإجمالياتها |
| `GET` | `/api/v1/vendor/wallet/transactions` | عرض سجل الحركات المالية |
| `GET` | `/api/v1/vendor/wallet/accounts` | عرض حسابات التحويل |
| `POST` | `/api/v1/vendor/wallet/accounts` | إضافة حساب تحويل |
| `GET` | `/api/v1/vendor/wallet/payouts` | عرض طلبات التحويل |
| `POST` | `/api/v1/vendor/wallet/payouts` | إنشاء طلب تحويل من الرصيد المتاح |
| `GET` | `/api/v1/vendor/settlements` | عرض التسويات الخاصة بالتاجر |

مثال إنشاء طلب تحويل:

```json
{
  "amount": 250.00,
  "payout_account_id": 1
}
```

### مسارات الإدارة

| Method | Endpoint | الوظيفة |
|---|---|---|
| `GET` | `/api/v1/admin/settlements` | عرض جميع التسويات |
| `POST` | `/api/v1/admin/settlements` | إنشاء تسوية لتاجر وفترة محددة |
| `POST` | `/api/v1/admin/settlements/{settlement}/approve` | اعتماد التسوية |
| `GET` | `/api/v1/admin/payouts` | عرض طلبات التحويل مع الفلترة بالحالة |
| `POST` | `/api/v1/admin/payouts/{payoutRequest}/approve` | اعتماد طلب التحويل |
| `POST` | `/api/v1/admin/payouts/{payoutRequest}/reject` | رفض الطلب وإعادة المبلغ للمتاح |
| `POST` | `/api/v1/admin/payouts/{payoutRequest}/paid` | تسجيل التحويل المنفذ ورقمه المرجعي |

قبل الإنتاج يجب ربط `markPaid` بمزود تحويل فعلي أو بتنفيذ يدوي موثق، والتحقق من webhook الخاص بمزود الدفع، وإضافة آلية استرداد تنشئ قيدًا عكسيًا بدل تعديل القيود القديمة.

