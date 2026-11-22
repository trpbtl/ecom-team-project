# Packe: Custom Packaging E-Commerce Platform

A business-to-business (B2B) e-commerce platform where businesses in Ghana design, preview, price, pay for and receive custom packaging online. Built as the team project for CS442 E-Commerce at Ashesi University.

**Live site:** [https://packe.onrender.com](https://packe.onrender.com)

---

## Table of Contents

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [System Architecture](#system-architecture)
4. [Project Structure](#project-structure)
5. [Technology Stack](#technology-stack)
6. [Database Schema](#database-schema)
7. [Installation Guide](#installation-guide)
8. [Server Deployment](#server-deployment)
9. [Usage Guide](#usage-guide)
10. [Endpoints](#endpoints)
11. [Security](#security)
12. [Contributors](#contributors)
13. [License](#license)

---

## Overview

**Packe** lets small and medium businesses (online shops, restaurants, cosmetics brands, manufacturers) order branded packaging without emails, phone calls or manual quotations. A customer designs their packaging in 3D, sees the price immediately, pays with mobile money or card, picks standard or express delivery, and Packe's own riders deliver it.

### Problem Statement

Getting custom packaging in Ghana usually means visiting a printer or exchanging messages to describe sizes and designs, waiting for a quote, and arranging delivery separately. Small businesses can't see what the finished packaging will look like before paying, and prices are rarely clear up front.

### Solution

Packe puts the whole process in one self-service website:
- Design any packaging type in an interactive 3D designer, with a logo, text, fonts, colours and patterns
- See the exact price before ordering, calculated from size, material, printing and quantity
- Pay securely with Paystack (mobile money or card)
- Drop a pin on a map to get a delivery price based on real road distance
- Track the order from payment to delivery

---

## Key Features

### For Customers (Businesses)

| Feature | Description |
|---------|-------------|
| 3D Packaging Designer | Design mailer boxes, food boxes, cartons, pouches, labels, stickers, seals, cards, brochures and catalogues, and see them fold in 3D |
| Branding Options | Upload a logo, add text, choose from six fonts, text size, text colour, italic, print colour, a custom colour wheel and nine optional patterns |
| Instant Pricing | Live price estimate in the browser; the server recalculates the real price before anything is charged |
| Shop and Search | Browse categories, search products and buy ready-made items or customise them |
| Cart | Add to cart without reloading the page; works for guests and carries over after login |
| Map-Based Checkout | Drop a pin, search an address or use your location; delivery is priced by road distance from Packe |
| Paystack Payment | Mobile money or card in Paystack's popup (test mode) |
| Accounts | Register with email and password or Continue with Google; edit details, change password, delete account |
| Order History | Invoices with the delivery location, order status and saved designs |
| Light and Dark Mode | A header toggle that remembers each visitor's choice |

### For Administrators

| Feature | Description |
|---------|-------------|
| Dashboard | Revenue, orders to prepare and orders on the road at a glance |
| Product Management | Add, edit and delete products with images, materials, keywords and design type |
| Category Management | Create and rename product categories |
| Order Management | View every order, change its status and assign a delivery rider |
| Business Accounts | Add, edit, search and delete accounts, change roles and reset passwords |

### For Delivery Staff

| Feature | Description |
|---------|-------------|
| My Deliveries | Assigned deliveries, express orders first |
| Directions | A "Directions in Google Maps" button to the customer's pin |
| Status Updates | Mark an order as picked up, then delivered |

### Pricing and Payment Rules

- **Product price:** `unit = (base + area × rate × material) + print or pattern + branding`, then a discount for larger quantity steps
- **Delivery price:** GH₵25 + GH₵1.50 per km from Ashesi University (standard); express is 1.8 times standard; up to 150 km within Ghana
- **Server-side checks:** prices and delivery fees are always recalculated in PHP, and every payment is verified with Paystack's API before an order is saved

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      CLIENT BROWSER                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────┐  │
│  │  HTML/CSS   │  │ JavaScript  │  │ Three.js (3D)       │  │
│  │  Tailwind   │  │  (vanilla)  │  │ Leaflet (map)       │  │
│  └─────────────┘  └─────────────┘  └─────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                PHP BACKEND (MVC, Apache)                    │
│  ┌──────────┐ ┌──────────┐ ┌──────────────┐ ┌────────────┐  │
│  │  Views   │ │ Actions  │ │ Controllers  │ │  Classes   │  │
│  │ (pages)  │ │ (forms)  │ │ (rules)      │ │  (SQL)     │  │
│  └──────────┘ └──────────┘ └──────────────┘ └────────────┘  │
│          core/: session, security, database connection      │
└─────────────────────────────────────────────────────────────┘
          │                                   │
          ▼                                   ▼
┌───────────────────────────┐   ┌─────────────────────────────┐
│      MySQL DATABASE       │   │      EXTERNAL SERVICES      │
│  Customers · Products     │   │  Paystack (payments)        │
│  Customizations · Cart    │   │  Google (sign-in)           │
│  Orders · Payments        │   │  OpenStreetMap / Nominatim  │
│  Deliveries               │   │  OSRM / OpenRouteService    │
└───────────────────────────┘   └─────────────────────────────┘
```

A request moves through the layers in one direction: a **view** shows a form, an **action** receives it and checks the CSRF token, a **controller** validates the input and applies the business rules, and a **class** reads or writes the database. The result travels back up as a redirect with a message, or as JSON when the page sent the request in the background.

---

## Project Structure

```
ecom-team-project/
├── actions/                      # One file per form or request (26)
│   ├── add_to_cart_action.php    # Add an item (JSON or redirect)
│   ├── save_design_action.php    # Save a 3D design and add it to the cart
│   ├── checkout_action.php       # Validate delivery details, price delivery
│   ├── delivery_quote.php        # Live delivery price for a map pin (JSON)
│   ├── process_payment_action.php # Verify Paystack payment, create the order
│   ├── google_login.php          # Start Google sign-in
│   ├── google_callback.php       # Finish Google sign-in
│   └── ...                       # Accounts, products, categories, orders, businesses
│
├── classes/                      # Models: all SQL lives here
│   ├── CustomerClass.php
│   ├── ProductClass.php
│   ├── CustomizationClass.php
│   ├── CartClass.php
│   ├── OrderClass.php
│   ├── DeliveryClass.php
│   └── BusinessClass.php
│
├── controllers/                  # Business rules: validation, pricing, payments
│   ├── Validate.php              # Shared form rules
│   ├── DesignTypes.php           # Packaging templates, fonts, patterns, pricing
│   ├── DeliveryPricing.php       # Road distance and delivery fees
│   ├── GoogleAuthController.php  # Google OAuth sign-in
│   ├── OrderController.php       # Checkout and Paystack verification
│   └── ...                       # Cart, Customer, Customization, Product, Delivery, Business
│
├── core/
│   ├── core.php                  # Session, helpers, CSRF, roles, uploads
│   ├── db_class.php              # Shared database connection, prepared statements
│   ├── db_cred.php               # Settings read from the environment
│   └── local_cred.example.php    # Template for local keys (real file is git-ignored)
│
├── views/                        # Pages (no SQL)
│   ├── home.php · all_products.php · single_product.php · search_results.php
│   ├── customize.php             # 3D designer
│   ├── cart.php · checkout.php · payment.php · payment_success.php
│   ├── login.php · register.php · complete_signup.php · privacy.php
│   ├── account/                  # My account, edit, change password, delete, order
│   ├── admin/                    # Dashboard, products, categories, orders, businesses
│   ├── delivery/                 # My deliveries
│   ├── layout/                   # Header, footer, sidebar
│   └── partials/                 # Product card, invoice, orders table, design summary
│
├── js/
│   ├── packaging3d.js            # 3D models for every packaging type
│   ├── designer.js               # 3D designer and live price
│   ├── hero3d.js                 # Home page 3D showcase
│   ├── cart.js                   # Add to cart without reloading
│   ├── delivery-map.js           # Checkout map, search and delivery quote
│   ├── validate.js               # Form checks in the browser
│   └── theme.js                  # Light and dark mode toggle
│
├── css/style.css                 # Shared components and themes
├── images/                       # Logo, catalogue illustrations, patterns, uploads
├── database/packe.sql            # Tables and starter products
├── setup.php                     # Creates or safely upgrades the database
├── Dockerfile · render.yaml      # Hosting on Render
└── README.md
```

---

## Technology Stack

### Frontend
| Technology | Purpose |
|------------|---------|
| HTML5 | Page structure |
| Tailwind CSS 3.4 (CDN) | Utility-first styling |
| JavaScript (ES6+) | Interactivity, live prices, background requests |
| Three.js 0.160 | 3D packaging designer and home page showcase |
| Leaflet 1.9 + OpenStreetMap | Checkout delivery map |

### Backend
| Technology | Purpose |
|------------|---------|
| PHP 8.3 | Server-side logic in an MVC structure (no framework) |
| MySQL 8 | Database |
| Apache | Web server |

### External Services and APIs
| Service | Purpose |
|---------|---------|
| Paystack | Mobile money and card payments, verified with the Verify API |
| Google OAuth 2.0 / OpenID Connect | "Continue with Google" sign-in |
| Nominatim (OpenStreetMap) | Address search at checkout |
| OSRM and OpenRouteService | Road distance for delivery pricing |
| Google Maps links | Free directions for riders |

### Hosting and Tools
| Tool | Purpose |
|------|---------|
| Render (Docker) | Hosts the live website |
| Aiven | Hosts the MySQL database |
| XAMPP | Local development server |
| Git and GitHub | Version control |

---

## Database Schema

### Entity Relationship Diagram

```
┌────────────────┐        ┌─────────────────┐       ┌────────────────┐
│   customers    │        │    products     │       │   categories   │
├────────────────┤        ├─────────────────┤       ├────────────────┤
│ customer_id PK │        │ product_id PK   │──────▶│ category_id PK │
│ full_name      │        │ category_id FK  │       │ category_name  │
│ email          │        │ name            │       └────────────────┘
│ password_hash  │        │ material        │
│ phone          │        │ base_price      │
│ business_name  │        │ design_type     │
│ city, address  │        └────────┬────────┘
│ user_role      │                 │
└───────┬────────┘                 │
        │      ┌───────────────────┴──────────────┐
        │      ▼                                  ▼
        │  ┌──────────────────┐          ┌──────────────────┐
        ├─▶│ customizations   │          │      cart        │
        │  ├──────────────────┤          ├──────────────────┤
        │  │ customization_id │◀─────────│ customization_id │
        │  │ size, material   │          │ cart_key         │
        │  │ logo, text, font │          │ product_id FK    │
        │  │ pattern          │          │ quantity         │
        │  │ unit_price       │          └──────────────────┘
        │  └──────────────────┘
        ▼
┌────────────────┐     ┌──────────────────┐     ┌────────────────────┐
│     orders     │────▶│   order_items    │     │    deliveries      │
├────────────────┤     ├──────────────────┤     ├────────────────────┤
│ order_id PK    │     │ order_id FK      │     │ order_id FK        │
│ customer_id FK │     │ product_id FK    │     │ recipient, address │
│ invoice_no     │     │ quantity         │     │ latitude/longitude │
│ subtotal       │     │ unit_price       │     │ distance_km        │
│ delivery_fee   │     └──────────────────┘     │ delivery_option    │
│ total_amount   │────────────────────────────▶ │ assigned_to FK     │
│ order_status   │     ┌──────────────────┐     │ status             │
└───────┬────────┘     │    payments      │     └────────────────────┘
        └─────────────▶├──────────────────┤
                       │ order_id FK      │     ┌────────────────────┐
                       │ amount, currency │     │   social_logins    │
                       │ channel          │     ├────────────────────┤
                       │ reference        │     │ customer_id FK     │
                       └──────────────────┘     │ provider (google)  │
                                                └────────────────────┘
```

### Table Descriptions

| Table | Description |
|-------|-------------|
| **customers** | Accounts for businesses, admins (role 1), customers (role 2) and delivery staff (role 3) |
| **social_logins** | Google accounts linked to Packe accounts |
| **categories** | Product groups such as Boxes, Labels and Food Packaging |
| **products** | Packaging products with base price, material and 3D template type |
| **customizations** | Each saved design: size, material, colours, logo, text, font, pattern, price |
| **cart** | Items in a cart, linked to a cookie key so guests can shop |
| **orders** | Paid orders with invoice number, totals and status |
| **order_items** | The products and designs in each order |
| **payments** | Paystack payment records: amount, channel and reference |
| **deliveries** | Recipient, map pin, distance, delivery option, rider and status |

---

## Installation Guide

### Prerequisites

- XAMPP (Apache, PHP 8, MySQL)
- Git
- A web browser (Chrome, Edge, Firefox or Safari)
- Optional: a free Paystack account (test mode) and Google Cloud OAuth keys

### Local Development Setup

1. **Clone the repository** into XAMPP's `htdocs` folder
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/trpbtl/ecom-team-project.git
   ```

2. **Start XAMPP**: open the XAMPP Control Panel and start **Apache** and **MySQL**.

3. **Create the database**: open `http://localhost/phpmyadmin` and import `database/packe.sql`. This creates the `packe` database with starter products.

4. **Add your keys (optional)**: copy `core/local_cred.example.php` to `core/local_cred.php` and add your Paystack **test** keys and Google keys. The database defaults (user `root`, no password) already match XAMPP.

5. **Open the site**
   ```
   http://localhost/ecom-team-project/
   ```

6. **Make an admin or rider**: register an account, then run in phpMyAdmin:
   ```sql
   UPDATE customers SET user_role = 1 WHERE email = 'you@example.com';   -- 1 = admin
   UPDATE customers SET user_role = 3 WHERE email = 'rider@example.com'; -- 3 = delivery staff
   ```
   After that, admins can change roles from **Admin → Businesses**.

---

## Server Deployment

The live site runs on **Render** (a Docker container with PHP 8.3 and Apache) with the database on **Aiven** MySQL, both on free plans.

1. **Database:** create a free MySQL service on Aiven and note the host, port, user and password. Download the CA certificate.
2. **Import the data:**
   ```bash
   C:\xampp\mysql\bin\mysql.exe -h YOUR-AIVEN-HOST -P YOUR-PORT -u avnadmin -p --ssl < database\packe.sql
   ```
3. **Web app:** on Render choose **New → Blueprint** and select this repository. Render reads `render.yaml` and builds from the `Dockerfile`.
4. **Environment variables** (entered in Render, never committed):

   | Variable | Value |
   |----------|-------|
   | `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS` | From Aiven |
   | `DB_NAME`, `DB_SSL`, `DB_SSL_CA` | `packe`, `true`, the text of `ca.pem` |
   | `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY` | Paystack test keys |
   | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | Optional, for Google sign-in |
   | `ORS_API_KEY` | Optional, OpenRouteService key for road distances |
   | `SUPPORT_EMAIL` | Optional, shown on the privacy page |

5. **After each deploy that changes the database**, open `/setup.php` once. It only adds missing tables and columns and never deletes data.

**Free plan notes:** Render's free service sleeps after 15 minutes without visits, so the first visit afterwards takes about a minute. Uploaded images are lost when the service redeploys, because its disk is temporary.

---

## Usage Guide

### For Customers

1. **Sign up or log in** with email and password, or with **Continue with Google**.
2. **Design packaging:** open **Design packaging**, pick a packaging type, set the size and material, add a logo, text, colours or a pattern, and turn the 3D model to check it.
3. **Add to cart:** choose a quantity. The price updates as you change the design.
4. **Check out:**
   - Drop a pin on the map, search your address, or use your location
   - Fill in the recipient's name and phone number
   - Choose standard or express delivery
5. **Pay** with mobile money or card in the Paystack popup. In test mode, use Paystack's test details; no real money moves.
6. **Track** the order and view its invoice from **My account**.

### For Administrators

1. Open **Dashboard** for a summary of revenue and orders.
2. Manage **Products** and **Categories**.
3. In **Orders**, update each order's status and assign a delivery rider.
4. In **Businesses**, add or edit accounts, change roles or reset passwords.

### For Delivery Staff

1. Open **My deliveries** to see assigned orders, express first.
2. Tap **Directions in Google Maps** to navigate to the customer's pin.
3. Mark each order **Picked up** and then **Delivered**.

---

## Endpoints

Every `POST` endpoint requires a valid CSRF token. Most redirect back with a message; those marked **JSON** answer background requests from the page.

### Cart and Checkout

| Endpoint | Method | Description |
|----------|--------|-------------|
| `actions/add_to_cart_action.php` | POST | Add a product to the cart (JSON or redirect) |
| `actions/update_qty_action.php` | POST | Change an item's quantity |
| `actions/remove_from_cart_action.php` | POST | Remove an item |
| `actions/save_design_action.php` | POST | Save a design, price it on the server, add it to the cart |
| `actions/delivery_quote.php?lat=…&lng=…` | GET | Delivery price for a map pin, logged-in users only (JSON) |
| `actions/checkout_action.php` | POST | Validate delivery details and prepare payment |
| `actions/process_payment_action.php` | GET | Verify the Paystack payment and create the order |

### Accounts

| Endpoint | Method | Description |
|----------|--------|-------------|
| `actions/register_action.php` | POST | Create an account |
| `actions/login_action.php` | POST | Log in with email and password |
| `actions/google_login.php` | GET | Start Google sign-in |
| `actions/google_callback.php` | GET | Finish Google sign-in |
| `actions/complete_signup_action.php` | POST | First-time Google users add their details |
| `actions/edit_account_action.php` | POST | Update account details |
| `actions/change_pass_action.php` | POST | Change or set a password |
| `actions/delete_account_action.php` | POST | Delete an account |

### Administration and Delivery

| Endpoint | Method | Description |
|----------|--------|-------------|
| `actions/add_product_action.php` / `update_product_action.php` | POST | Add or edit a product |
| `actions/add_category_action.php` / `update_category_action.php` | POST | Add or rename a category |
| `actions/update_order_status_action.php` | POST | Change an order's status |
| `actions/assign_delivery_action.php` | POST | Assign a rider to an order |
| `actions/add_business_action.php` / `update_business_action.php` | POST | Add or edit an account |
| `actions/update_business_role_action.php` | POST | Change an account's role |
| `actions/delete_business_action.php` | POST | Delete an account with no orders |
| `actions/update_delivery_status_action.php` | POST | Rider marks picked up or delivered |

---

## Security

| Risk | Protection | Where |
|------|------------|-------|
| SQL injection | Every query is a prepared statement with bound values | `core/db_class.php` |
| Stolen passwords | Only bcrypt hashes are stored (`password_hash` / `password_verify`) | `classes/CustomerClass.php` |
| Forged forms (CSRF) | A random token per session, checked with `hash_equals` | `require_post()` in `core/core.php` |
| Session hijacking | New session ID at login; HttpOnly, SameSite and Secure cookies | `core/core.php` |
| Script injection (XSS) | All output is escaped with `e()` (`htmlspecialchars`) | Every view |
| Wrong role | `require_login()`, `require_admin()`, `require_delivery()`; the role is re-read from the database on every page | `core/core.php` |
| Payment tampering | Amount, currency and reference are verified with Paystack's API on the server | `controllers/OrderController.php` |
| Harmful uploads | Real file type checked with `finfo`, size limits, random file names | `save_uploaded_image()` |
| Leaked keys | Keys live in environment variables or the git-ignored `core/local_cred.php` | `core/db_cred.php` |

---

## Contributors

| Name | Role |
|------|------|
| Hagen Dickson | User management and authentication, product catalogue, admin business accounts |
| Josephine Allan | 3D packaging designer, customisation and pricing, CMS version |
| Donna Omaboe | Shopping cart, checkout and Paystack payment |
| Eyram-Makafui Awoye | Order management, delivery management and pricing, administration, Google sign-in |

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Acknowledgments

- Ashesi University, CS442 E-Commerce
- [Three.js](https://threejs.org/) for 3D rendering
- [Leaflet](https://leafletjs.com/) and [OpenStreetMap](https://www.openstreetmap.org/) contributors for maps
- [Paystack](https://paystack.com/) for payments
- [Tailwind CSS](https://tailwindcss.com/) for styling

---

## Repository

GitHub: [https://github.com/trpbtl/ecom-team-project](https://github.com/trpbtl/ecom-team-project)

Design document: [System Development Lifecycle: Design and Architecture](docs/System-Development-Lifecycle-Design-and-Architecture.docx)

Optional CMS version: [https://josephineallan369.wixsite.com/packe-1](https://josephineallan369.wixsite.com/packe-1)

---

*Packe: Custom Packaging E-Commerce Platform. CS442 E-Commerce*
*Ashesi University, 2026*
