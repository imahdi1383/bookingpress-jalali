# BookingPress Jalali

## هدف پروژه

این پروژه یک افزونه مستقل WordPress برای افزودن پشتیبانی کامل از تقویم Jalali/Persian به BookingPress است.

افزونه باید در کنار BookingPress Lite و BookingPress Pro نصب شود و هرگز فایل‌های اصلی BookingPress را تغییر ندهد.

هدف اصلی پروژه این است که تجربه کار با تقویم BookingPress را با حفظ UI و رفتار اصلی آن، برای تقویم Jalali فراهم کند.

---

## Calendar Modes

سیستم باید حداقل دو حالت داشته باشد:

- `jalali`
- `gregorian`

حالت پیش‌فرض:

`jalali`

اما UI اولیه پروژه انگلیسی است.

نمونه:

`Mehr 1405`

نه:

`مهر ۱۴۰۵`

ترجمه UI در این مرحله جزو scope نیست و بعداً با Loco Translate انجام خواهد شد.

---

## Calendar Toggle
در تنظیمات افزونه، یک تنظیم پیش فرض برای تقویم وجود دارد که به صورت دیفالت روی Jalali تنظیم شده است. که از طریق پنل مدیریتی وردپرس قابل تغییر است. تغییر این تنظیم، دیفالت همه تقویم ها را برای همه کاربران به گزینه انتخابی تغییر میدهد.
اما هر بازدید کننده باید بتواند بدون deactivate یا activate کردن افزونه، حالت تقویم را تغییر دهد.

نمونه:

`Jalali ↔ Gregorian`

این تغییر باید در سطح Calendar Instance قابل مدیریت باشد.

اگر یک صفحه چند Calendar یا Booking Form داشته باشد، نباید معماری به‌صورت پیش‌فرض همه instanceها را به یک state مشترک وابسته کند.

---

## اصل بسیار مهم: Gregorian Canonical Data

BookingPress باید همچنان تاریخ و زمان را در قالب و semantics مورد انتظار خودش دریافت و ذخیره کند.

Jalali فقط یک Presentation/Input Layer است.

هرگز نباید تاریخ Jalali را به‌عنوان canonical date در دیتابیس یا قرارداد داخلی BookingPress ذخیره کنیم، مگر اینکه پس از بررسی سورس ثابت شود BookingPress برای یک مورد خاص قرارداد متفاوتی دارد.

الگوی اصلی:

User Input
→ Calendar Adapter
→ Gregorian
→ BookingPress

و:

BookingPress
→ Gregorian
→ Calendar Adapter
→ Jalali UI

---

## Calendar Correctness

پیاده‌سازی Jalali باید از قوانین واقعی تقویم جلالی پیروی کند.

موارد مهم:

- leap years
- leap-year boundaries
- Farvardin 1
- Esfand length
- 29/30 day Esfand
- month boundaries
- year boundaries
- Gregorian ↔ Jalali conversion
- date validation
- timezone-sensitive date/time behavior

از الگوریتم ساده یا تقریبی برای تشخیص سال کبیسه استفاده نکن.

اگر از یک library استفاده می‌شود، باید قبل از انتخاب آن از نظر correctness، maintenance، license، bundle size و test coverage بررسی شود.

---

## BookingPress Compatibility

هرگز فایل‌های BookingPress Lite یا Pro را مستقیماً ویرایش نکن.

پوشه‌های مرجع:

- `bookingpress-appointment-booking`
- `bookingpress-appointment-booking-pro`

این دو پوشه فقط برای مطالعه، بررسی API، رفتار داخلی و compatibility هستند.

محل قابل ویرایش پروژه:

- `bookingpress-jalali`

است.

---

## UI Preservation

تا حد امکان UI موجود BookingPress باید بدون تغییر باقی بماند.

نباید Calendar UI جدیدی از صفر ساخته شود مگر اینکه بررسی سورس ثابت کند این کار ضروری است.

تغییرات UI باید حداقلی باشند و با:

- typography
- spacing
- colors
- borders
- controls
- responsive behavior

موجود BookingPress هماهنگ باشند.

Toggle تقویم نیز باید تا حد امکان شبیه یک کنترل native در UI BookingPress باشد.

---

## WordPress Compatibility

افزونه باید:

- مستقل باشد
- namespace و prefix اختصاصی داشته باشد
- با WordPress coding conventions سازگار باشد
- بدون تغییر core BookingPress کار کند
- در صورت نبود BookingPress gracefully fail کند

---

## زبان پروژه

مستندات و توضیحات Agentها می‌توانند فارسی باشند.

کد پروژه باید انگلیسی باشد.

نام‌های زیر باید انگلیسی باشند:

- classes
- methods
- functions
- variables
- namespaces
- hooks
- CSS classes
- JavaScript identifiers
- file names

UI strings اولیه نیز انگلیسی باشند تا بعداً توسط Loco Translate ترجمه شوند.

---

## Timezone

در تمام عملیات date/time باید timezone به‌صورت صریح و قابل ردیابی در نظر گرفته شود.

از تبدیل‌های implicit و وابسته به timezone سیستم کاربر تا حد امکان اجتناب شود.

Calendar Date و DateTime را از یکدیگر تفکیک کن.

---

## معماری

قبل از پیاده‌سازی featureهای پیچیده، ابتدا معماری و قرارداد integration با BookingPress مشخص شود.

ترجیح:

- Adapter Pattern
- Separation of concerns
- Pure calendar calculations
- مستقل بودن Calendar Engine از BookingPress
- Integration Layer جدا از Calendar Engine
- UI Layer جدا از conversion logic

Calendar calculations نباید مستقیماً با DOM یا BookingPress API مخلوط شوند.

---

## Testing

هر تغییر مهم باید test داشته باشد.

حداقل مواردی که باید پوشش داده شوند:

- Gregorian leap years
- Jalali leap years
- month boundaries
- year boundaries
- Farvardin 1
- Esfand 29
- Esfand 30
- Gregorian ↔ Jalali round trips
- invalid dates
- timezone edge cases
- BookingPress date submission
- calendar mode switching
- multiple calendar instances
- UI regression

---

## Agent Safety

هیچ Agentی نباید بدون نیاز فایل‌های خارج از `bookingpress-jalali` را تغییر دهد.

BookingPress Lite و Pro منابع read-only هستند.

هیچ Agentی نباید:

- فایل‌های BookingPress را patch کند
- فایل‌های BookingPress را delete کند
- dependency خارجی را بدون بررسی اضافه کند
- plugin دیگری را uninstall کند
- WP-Parsidate را نصب کند
- داده‌های واقعی WordPress را حذف کند

اگر انجام کاری خارج از این محدوده لازم است، ابتدا از کاربر اجازه بگیر.

---

## عدم قطعیت

Agent نباید رفتار داخلی BookingPress را حدس بزند.

اگر چیزی درباره BookingPress مشخص نیست:

1. ابتدا سورس محلی را بررسی کن.
2. سپس documentation رسمی را بررسی کن.
3. در صورت نیاز GitHub یا منابع معتبر را بررسی کن.
4. نتیجه را با evidence گزارش کن.
5. در صورت باقی ماندن ابهام، قبل از implementation سؤال بپرس.

---

## Change Discipline

تغییرات باید:

- قابل review
- قابل rollback
- قابل test

باشند.

از refactorهای غیرضروری در کنار feature اصلی خودداری کن.

---

## Documentation Language

توضیحات پروژه، architecture notes، Agent instructions و Skills ترجیحاً فارسی باشند.

اصطلاحات فنی استاندارد را به انگلیسی نگه دار.

مثال:

`Calendar Adapter`

بهتر از ترجمه مصنوعی آن است.

---

## Rule of Last Resort

اگر برای پیاده‌سازی یک feature مجبور شدی BookingPress core را تغییر دهی، implementation را متوقف کن و ابتدا دلیل آن را گزارش کن.

راه‌حل extension-based باید قبل از هر core modification بررسی شود.
