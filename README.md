# Custom Packaging E-Commerce Platform

A web-based **business-to-business (B2B) e-commerce platform** that lets businesses in Ghana design, customize, preview, price and order packaging products online, with delivery handled by the company's own delivery team.

The platform is for SMEs, online retailers, restaurants, cosmetics brands, manufacturers and other businesses that need custom packaging. Customers serve themselves, so they don't have to contact the company for manual quotes or order processing. Both **standard** and **express** delivery are supported, and rush orders are priced dynamically.

---

## Features

- **Product catalogue**: browse packaging products with descriptions, sizes, materials, images and prices
- **Packaging customization**: choose type, dimensions, colours, materials, quantity and branding
- **Design preview**: see the customized packaging before ordering
- **Dynamic pricing**: prices are calculated from the specifications, quantity and customization
- **Cart and checkout**: add custom products, adjust quantities and review the order
- **Online payments**: pay through an integrated payment gateway
- **Order management**: administrators track and update order statuses
- **Delivery management**: standard or express delivery, assignment to delivery staff, and tracking
- **Admin dashboard and reporting**: manage customers, products, orders, payments and deliveries, and generate business reports

---

## Tech Stack

| Component               | Technology                 | Purpose                                                              |
|-------------------------|----------------------------|----------------------------------------------------------------------|
| Frontend                | HTML5, CSS3, JavaScript    | Website interface, interactive customization and price calculations |
| Backend                 | PHP                        | Application logic, orders, payments and delivery operations          |
| Database                | MySQL                      | Customers, products, customizations, orders, payments and deliveries |
| Web server              | Apache                     | Hosts and serves the application                                     |
| Development environment | XAMPP                      | Local PHP and MySQL environment                                      |

---

## Architecture

The system has three tiers:

```
            USERS
  Customers  |  Administrators  |  Delivery Personnel
                     │
┌────────────────────▼─────────────────────┐
│ PRESENTATION LAYER                       │
│ Customer Website · Admin Dashboard ·     │
│ Delivery Dashboard                       │
└────────────────────┬─────────────────────┘
┌────────────────────▼─────────────────────┐
│ APPLICATION / BUSINESS LOGIC LAYER (PHP) │
│ Auth · Catalogue · Customization ·       │
│ Pricing · Cart & Checkout · Payments ·   │
│ Orders · Delivery · Notifications ·      │
│ Reporting                                │──► Payment Gateway (external)
└────────────────────┬─────────────────────┘
┌────────────────────▼─────────────────────┐
│ DATABASE LAYER (MySQL)                   │
│ Customers · Admins · Products ·          │
│ Customizations · Cart · Orders ·         │
│ Payments · Deliveries                    │
└──────────────────────────────────────────┘
```

---

## How It Works

1. Register or log in
2. Browse the catalogue and choose a product
3. Customize and preview the packaging
4. See the calculated price and add the product to the cart
5. Check out and enter delivery details
6. Choose standard or express delivery
7. Pay through the payment gateway
8. The order is created and processed by an admin
9. The internal delivery team delivers the order and updates its status

---

## Team and Responsibilities

| Member              | Modules                                                       |
|---------------------|---------------------------------------------------------------|
| **Hagen Dickson**   | User Management, Authentication, Product Catalogue            |
| **Josephine Allan** | Packaging Customization, Preview, Pricing                     |
| **Donna Omaboe**    | Shopping Cart, Checkout, Payment                              |
| **Eyram Awoye**     | Order Management, Delivery Management, Administration and Reporting |

---

## Getting Started (Local Setup)

1. Install [XAMPP](https://www.apachefriends.org/).
2. Clone this repository into XAMPP's `htdocs` folder:
   ```bash
   git clone https://github.com/trpbtl/ecom-team-project.git
   ```
3. Start **Apache** and **MySQL** from the XAMPP Control Panel.
4. Open phpMyAdmin (`http://localhost/phpmyadmin`) and import `database/schema.sql`.
5. Open `http://localhost/ecom-team-project` in your browser.

---

## Project Structure

```
ecom-team-project/
├── index.php            Homepage
├── config/db.php        MySQL connection
├── database/schema.sql  Tables: customers, admins, products, customizations,
│                        cart, orders, order_items, payments, deliveries
├── includes/            Shared header and footer
├── assets/              style.css, script.js
├── pages/               Customer pages: login, register, catalogue, product,
│                        customize, preview, cart, checkout, payment, track-order
├── admin/               Admin dashboard, orders, products, customers, reports
├── delivery/            Delivery personnel dashboard
├── controllers/         Application layer: Auth, Product, Customization, Pricing,
│                        Cart, Payment, Order, Delivery, Admin
└── docs/                Design and architecture document
```

## Documentation

- [System Development Lifecycle: Design and Architecture](docs/System-Development-Lifecycle-Design-and-Architecture.docx)
- Optional CMS version: https://josephineallan369.wixsite.com/packe

---

## Development Lifecycle

The project follows the Web Site Systems Development Life Cycle:

1. **Systems Analysis / Planning**: business objectives, requirements and functionality
2. **Systems Design**: architecture, database, UI layouts and workflows
3. **Building the System**: HTML/CSS/JS frontend, PHP backend, MySQL database
4. **Testing**: login, customization, pricing, cart, payments, orders and delivery
5. **Implementation / Service Delivery**: deployment, maintenance and support
