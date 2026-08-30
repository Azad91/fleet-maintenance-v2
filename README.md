# Fleet Control

Fleet Control avtobus parkının texniki istismarını idarə etmək üçün hazırlanmış Laravel tətbiqidir. Sistem bir neçə şirkət və qarajla işləyir; məlumat, istifadəçi rolu və əməliyyatlar cari qaraj üzrə ayrılır.

## Əsas imkanlar

- Avtobusların, xətt nömrələrinin, DQN/VIN və son yürüşün idarə edilməsi
- Günlük KM və günlük status qeydləri
- Nasazlıq, qəza və texniki xidmət kartlarının açılması
- Texniki xidmətdə növbəti yağdəyişmə intervalına görə detalların avtomatik gətirilməsi
- Kartda istifadə olunan detalın, miqdarın və işi görən işçinin qeyd edilməsi
- Stok qalığının izlənməsi və mənfi stokun qarşısının alınması
- Şikayət kartının PDF akt kimi açılması/çap edilməsi
- Excel-dən avtobus, anbar, işçi, sürücü, günlük KM, günlük status və digər məlumatların idxalı
- Sürücülərin Excel-ə ixracı
- Şirkət → qaraj → məlumat quruluşu
- Rol əsaslı menyu və icazələr
- Açıq və tünd rejim

## Sistem quruluşu

```text
Şirkət
└── Qaraj
    ├── Avtobuslar
    ├── Kartlar / Şikayətlər
    ├── Anbar
    ├── Günlük KM
    ├── Günlük statuslar
    ├── Sürücülər
    └── İşçilər
```

İstifadəçi yalnız ona təyin edilmiş qarajdakı məlumatları görür.

## Rollar və icazələr

| Rol | İcazə |
| --- | --- |
| `admin` | Təyin olunduğu qarajda bütün əməliyyatlar və istifadəçi idarəetməsi |
| `complaint` | Kart/şikayət yaratmaq, redaktə etmək və idarə etmək |
| `warehouse` | Anbar məhsullarını və stokunu idarə etmək |
| `daily_km` | Günlük KM qeydlərini idarə etmək |
| `daily_status` | Günlük status qeydlərini idarə etmək |
| `directorate` | Müvafiq bölmələrə yalnız baxış; yaratma, redaktə və silmə yoxdur |

Admin yeni hesabları **İstifadəçilər** bölməsindən yaradır, cari qaraja bağlayır, rol verir, hesabı aktiv/passiv edir və şifrəni yeniləyə bilir.

## Tələblər

- PHP 8.3+
- Composer
- PostgreSQL 14+
- PHP genişlənmələri: `pdo_pgsql`, `mbstring`, `xml`, `zip`, `gd`

## Quraşdırma

```bash
git clone https://github.com/Azad91/fleet-maintenance-v2.git
cd fleet-maintenance-v2
composer install
copy .env.example .env
php artisan key:generate
```

`.env` faylında PostgreSQL bağlantısını öz mühitinizə uyğun yazın:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=fleet_maintenance_v2
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Sonra migration və başlanğıc məlumatları yaradın:

```bash
php artisan migrate --seed
php artisan serve
```

Tətbiq standart olaraq `http://127.0.0.1:8000` ünvanında açılır.

> Mövcud məlumatları olan bazada `migrate:fresh` işlətməyin; bu əmr cədvəlləri silir. Mövcud sistem üçün yalnız `php artisan migrate` istifadə edin.

## İlkin giriş

Seeder ilə aşağıdakı admin hesabı yaradılır:

```text
E-mail: admin@fleet.com
Şifrə: password
```

İlk girişdən sonra şifrəni **Profil ayarları** bölməsindən dəyişin. Admin daxil olduqdan sonra qaraj seçir və **İstifadəçilər** bölməsindən yeni hesablar yaradır.

## Excel idxalı

Hər idxal səhifəsində qəbul olunan sütunlar göstərilir. Fayllar `.xlsx`, `.xls` və ya `.csv` formatında, maksimum 10 MB ola bilər.

Avtobus idxalında `DQN` məcburidir. Eyni DQN cari qarajda yenilənir; başqa qaraja aid DQN isə idxal edilmir.

## Texniki xidmət kartı

1. Kart açarkən avtobus və texniki xidmət tipi seçilir.
2. Sistem avtobusun son KM göstəricisini götürür.
3. Motor yağı cədvəlindən son KM-dən böyük olan ilk interval seçilir.
4. Həmin intervalın detallar siyahısı avtomatik əlavə edilir.
5. Dəyişməyəcək detal varsa, kartdakı **Detalı sil** düyməsi ilə çıxarılır. Silinən detal anbardan düşmür.

## Testlər

```bash
php artisan test
```

## Texnologiyalar

- Laravel 13
- PHP 8.3+
- PostgreSQL
- Bootstrap 5
- Laravel Excel (`maatwebsite/excel`)
- DomPDF (`barryvdh/laravel-dompdf`)

## Təhlükəsizlik qeydləri

- `.env` faylını GitHub-a göndərməyin.
- Production mühitində `APP_DEBUG=false` istifadə edin.
- İlkin admin şifrəsini dərhal dəyişin.
- İstifadəçilərə yalnız ehtiyac duyduğu qaraj və rolu verin.
