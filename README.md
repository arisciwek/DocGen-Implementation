# DocGen Implementation Plugin

WordPress plugin yang mengimplementasikan WP DocGen untuk berbagai kebutuhan pembuatan dokumen otomatis. Plugin ini menyediakan contoh penggunaan WP DocGen untuk beberapa skenario umum seperti company profile, tabel data, dan chart.

## 🌟 Features

- **Company Profile Generator**
  - Display data perusahaan dari JSON
  - Template profesional
  - Download dalam format DOCX/PDF
  - Preview template

- **Coming Soon**
  - Table Sample Generator
  - Chart Sample Generator

## 📋 Requirements

- WordPress 5.8+
- PHP 7.4+
- [WP DocGen Plugin](https://github.com/your-repo/wp-docgen)
- PHP Extensions:
  - zip
  - xml
  - fileinfo

## 🚀 Installation

1. Install dan aktifkan [WP DocGen Plugin](https://github.com/your-repo/wp-docgen)
2. Download ZIP file dari repository ini
3. Upload plugin melalui menu Plugins > Add New > Upload Plugin di WordPress Admin
4. Aktifkan plugin melalui menu 'Plugins'
5. Akses menu 'DocGen Samples' di admin dashboard

## 💡 Usage

### Company Profile Generator
1. Navigasi ke DocGen Samples > Company Profile
2. Review data company profile yang ditampilkan
3. Preview template document
4. Klik Download untuk generate dokumen

## 📋 Sample Data

### Company Profile
```json
{
  "company_name": "PT Awesome Tech",
  "tagline": "Creating Tomorrow's Solutions",
  "address": "Jl. Innovation Boulevard No.123, Jakarta",
  "phone": "+62 21 1234567",
  "email": "hello@awesome-tech.id",
  "website": "www.awesome-tech.id",
  "vision": "Menjadi perusahaan teknologi terdepan di Indonesia",
  "mission": [
    "Menghadirkan solusi inovatif untuk setiap klien",
    "Memberikan layanan berkualitas tinggi",
    "Berkontribusi pada kemajuan teknologi nasional"
  ],
  "established": "2020",
  "employees": "150+",
  "services": [
    "Software Development",
    "Cloud Solutions",
    "Digital Transformation",
    "IT Consulting"
  ]
}
```

## 📁 Plugin Structure

```
docgen-implementation/
├── admin/              # Admin interface files
├── templates/          # Document templates
│   └── data/          # JSON sample data
├── includes/          # Core plugin files
├── assets/           # CSS, JS, dan file pendukung
└── docgen-implementation.php
```

## 🤝 Contributing

Kontribusi akan sangat dihargai:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

### Development Version

Untuk developer yang ingin menggunakan Composer:
1. Clone repository
2. Checkout ke branch `dev-composer`
3. Jalankan `composer install`
4. Jalankan `composer test`

## 📝 ToDo

- [ ] Company Profile Generator
  - [ ] JSON data integration
  - [ ] Template design
  - [ ] Preview functionality
  - [ ] Download implementation

- [ ] Table Sample Generator
  - [ ] Sample JSON data
  - [ ] Table template
  - [ ] Data processing

- [ ] Chart Sample Generator
  - [ ] Sample chart data
  - [ ] Chart generation
  - [ ] Multiple chart types

## 📄 License

Distributed under the GPL v2 or later. See `LICENSE` for more information.

## 👥 Authors

- **Your Name** - *Initial work* - [arisciwek](https://github.com/arisciwek/)

## 🙏 Acknowledgments

- [WP DocGen](https://github.com/arisciwek/wp-docgen)
- [PHPWord](https://github.com/PHPOffice/PHPWord)
