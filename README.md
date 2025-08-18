# DMS (Distribution Management System)

## Overview
DMS is a comprehensive Distribution Management System designed for hybrid business operations, supporting both direct sales (admin-driven) and customer orders (e-commerce). It is built for steel and industrial product management, providing robust inventory, sales, purchase, and customer management features.

## Features
- **User Management:** Admin, Manager, Salesperson, Inventory Manager, and Customer roles.
- **Inventory Management:** Categories, items, stock tracking, media management, and stock alerts.
- **Sales & Orders:**
  - Direct sales (admin): Single-item, single-record sales.
  - Customer orders: Multi-item, e-commerce style orders, with fulfillment and conversion to sales.
  - Invoice and payment management.
- **Purchases:** Vendor management, purchase orders, and raw material tracking.
- **Reports & Analytics:** Exportable reports, sales/purchase/inventory/customer analytics, and chart visualizations.
- **Security:** Role-based access, password management, and email verification.
- **ACID Compliance:** Database and application logic ensure data integrity and reliability.

## Tech Stack
- **Backend:** PHP (PDO, MySQL)
- **Frontend:** HTML, CSS (Bootstrap), JavaScript (jQuery, Chart.js)
- **Database:** MySQL (InnoDB)
- **PDF/Export:** dompdf

## Folder Structure
- `api/` — RESTful endpoints for admin, customer, sales, reports, etc.
- `model/` — Business logic for inventory, sales, purchase, user, etc.
- `views/` — Web interface for admin and customers.
- `assets/` — CSS, JS, images, and vendor libraries.
- `inc/` — Configuration, helpers, and shared includes.
- `uploads/` — User-uploaded files (exports, invoices, profile images).
- `templates/` — PDF and report templates.

## Setup Instructions
1. **Clone the Repository:**
   ```
   git clone <https://github.com/hamzaiqbal35/DMS.git>
   ```
2. **Database Setup:**
   - Import `inc/config/database.sql` into your MySQL server.
   - Update `inc/config/database.php` with your DB credentials.
3. **Dependencies:**
   - Install PHP dependencies (if using Composer):
     ```
     composer install
     ```
   - All required JS/CSS libraries are included in `assets/vendor/`.
4. **Web Server:**
   - Place the project in your web server root (e.g., `htdocs` for XAMPP).
   - Ensure PHP and MySQL are running.
5. **Access the App:**
   - Open `http://localhost/DMS/` in your browser.
   - Default admin login: `admin35@gmail.com` / `admin3535` (change after first login).

## Usage
- **Admin Panel:** Manage inventory, sales, purchases, users, and view analytics.
- **Customer Portal:** Register, browse catalogue, place orders, view order history.
- **Reports:** Export sales, purchase, and inventory data as PDF/Excel.

## Security Notes
- Change default admin credentials after setup.
- Use HTTPS in production.
- Regularly back up your database and uploaded files.

## License
This project is proprietary. For licensing, contact the author.

## Author
Hamza Iqbal

---
*For support or questions, please contact: hamzaiqbalrajpoot35@gmail.com* 
