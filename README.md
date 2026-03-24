<div align="center">

# ⚖️ Get Lawyer App
### منصة المحاماة الحرّة

**A freelance legal services platform connecting clients with verified lawyers and law firms.**

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-Token_Auth-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)

</div>

---

## 📖 About

منصة متكاملة بنظام العمل الحر تربط بين العملاء والمحامين/مكاتب المحاماة. يتيح النظام للعملاء نشر قضاياهم وتلقي عروض أسعار من محامين موثقين، مع وجود لوحة تحكم إدارية لإدارة التوثيق والموظفين.

---

## 🚀 Core Features

- **Multi-Role System** — Admin, Staff, Client, Lawyer, Law Firm
- **Provider Verification** — Document upload & admin review workflow
- **Case Lifecycle** — Publish → Offers → Accept → Execute → Close
- **Notification System** — Real-time database notifications for all events
- **Admin Dashboard** — Platform statistics, verification management, staff control

---

## 🛠 Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 |
| Language | PHP 8.3+ |
| Database | MySQL |
| Authentication | Laravel Sanctum (Token-based) |
| Architecture | RESTful API |

---

## 🏗 Database Schema

| Table | Description |
|-------|-------------|
| `users` | All user types (clients, lawyers, firms, staff, admins) |
| `provider_profiles` | Verification data & bio for lawyers and law firms |
| `legal_cases` | Cases published by clients |
| `offers` | Price offers submitted by providers on cases |
| `notifications` | Laravel database notifications |

---

## 📡 API Endpoints

### 🔓 Public

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/register` | Register new account (client / lawyer / law firm) |
| `POST` | `/api/login` | Login and receive Bearer token |

### 👤 Client

> Requires: `Authorization: Bearer {token}`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/client/cases` | List all cases for the authenticated client |
| `POST` | `/api/client/cases` | Publish a new legal case |
| `GET` | `/api/client/cases/{id}/offers` | View all offers on a specific case |
| `POST` | `/api/client/offers/{id}/accept` | Accept an offer and begin execution |
| `PATCH` | `/api/client/cases/{id}/status` | Update case status (completed / unresolved) |

### ⚖️ Provider (Lawyer / Law Firm)

> Requires: `Authorization: Bearer {token}` + verified account

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/provider/upload-docs` | Upload verification documents (ID, license, etc.) |
| `GET` | `/api/provider/cases` | Browse available pending cases |
| `POST` | `/api/provider/cases/{id}/offers` | Submit a price offer on a case |

### 🛡 Admin & Staff

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/admin/dashboard` | Platform-wide statistics |
| `GET` | `/api/admin/pending-lawyers` | List verification requests awaiting review |
| `POST` | `/api/admin/verify-lawyer/{id}` | Approve or reject a lawyer with notes |
| `POST` | `/api/admin/add-staff` | Add a platform staff member *(Admin only)* |

---

## ⚙️ Installation

```bash
# 1. Clone the repository
git clone https://github.com/AbdalrhmanAbdoAlhade/get-lawyer-app.git
cd get-lawyer-app

# 2. Install dependencies
composer install

# 3. Environment setup
cp .env.example .env
# Edit .env with your database credentials

# 4. Generate key & migrate
php artisan key:generate
php artisan migrate

# 5. Link storage
php artisan storage:link

# 6. Run the server
php artisan serve
```

> API will be available at: `http://127.0.0.1:8000`

---

## 📝 Notes

- All protected routes require `Authorization: Bearer {token}` in the request header.
- Provider verification is **mandatory** before submitting any offers.
- Use [Postman](https://www.postman.com/) to explore and test the API.
- Staff accounts can only be created by users with the **Admin** role.

---

## 📩 Contact

**Abdalrhman Abdo Alhade**

[![GitHub](https://img.shields.io/badge/GitHub-@AbdalrhmanAbdoAlhade-181717?style=flat&logo=github)](https://github.com/AbdalrhmanAbdoAlhade)
[![Email](https://img.shields.io/badge/Email-abdo.king22227@gmail.com-D14836?style=flat&logo=gmail&logoColor=white)](mailto:abdo.king22227@gmail.com)
