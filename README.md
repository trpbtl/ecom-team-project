# Packe: Custom Packaging E-Commerce Platform

A web-based **business-to-business (B2B) e-commerce platform** where businesses in Ghana design, preview, price and order custom packaging online, with delivery handled by Packe's own delivery team.

The platform is for SMEs, online retailers, restaurants, cosmetics brands and manufacturers that need custom packaging. Customers serve themselves: they design a box in 3D, see the price straight away, pay with Paystack and choose **standard** or **express** delivery.

---

## Features

- **3D designer for every packaging type**: mailer boxes, takeaway food boxes (sealed with a round logo sticker), shipping cartons, stand-up pouches, labels, round stickers, tamper seals, cards, tri-fold brochures and catalogues. Set the size, material, print colour, logo and text, and watch it in 3D (boxes, brochures and catalogues fold and unfold). A flat layout view shows cut and fold lines. The templates live in `controllers/DesignTypes.php`.
- **Instant pricing**: the browser shows an estimate and the server calculates the real price from size, material, print, branding and quantity step, using the same rules.
- **Shop**: categories, search and product pages. Every product can be bought ready-made, or designed.
- **Home page showcase**: the 3D window on the home page cycles through a mailer box, food box, pouch, carton, catalogue and sticker, using the same models as the designer (`js/packaging3d.js`).
- **Add to cart without reloading**: the cart badge updates the moment you click; the item is saved in the background (`js/cart.js`). Adding the same item again raises its quantity.
- **Cart**: works for guests (a cookie keeps the cart) and carries over after login.
- **Checkout with delivery**: recipient, address, and standard or express delivery.
- **Paystack payments (test mode)**: mobile money or card in Paystack's popup. Each payment is **verified on the server** with Paystack's API before an order is recorded.
- **Customer accounts**: register, log in, edit details, change password, delete account, order history and saved designs.
- **Admin**: dashboard, products (with image upload), categories, orders, order status, and assigning deliveries to riders.
- **Delivery staff**: a page listing their deliveries, express first, with buttons to mark them picked up and delivered.

---

## Tech stack

| Part | Technology |
|---|---|
| Views | PHP templates, HTML, Tailwind CSS (CDN), vanilla JavaScript |
| 3D designer | Three.js (WebGL) |
| Backend | PHP 8 in an MVC structure |
| Database | MySQL |
| Payments | Paystack (test mode) |
| Hosting | Render (Docker) and Aiven MySQL, both on free plans |

---

## How the code is organised (MVC)

```
core/          db_cred.php (settings), db_class.php (connection), core.php (session, helpers, access control)
classes/       Models: all SQL lives here (CustomerClass, ProductClass, CustomizationClass, CartClass, OrderClass, DeliveryClass)
controllers/   Validate input, call the models, return result arrays (no SQL, no HTML)
actions/       One file per form: read $_POST, call a controller, set a message, redirect
views/         Pages: HTML with loops and conditions only (no SQL)
  layout/      header.php, footer.php, sidebar.php
  partials/    product card, invoice, orders table, design summary
  account/     my account, edit details, change password, delete account, order
  admin/       dashboard, products, categories, orders, order detail
  delivery/    my deliveries (delivery staff)
js/            validate.js (form checks), cart.js (add to cart without reloading), packaging3d.js (3D models for every type),
               designer.js (3D designer), hero3d.js (home page showcase)
css/           style.css
images/        logo, placeholder, products/ and designs/ (uploads)
database/      packe.sql (tables and starter products)
```

The rules followed throughout:
- Every query uses **prepared statements**.
- Passwords use **`password_hash()` / `password_verify()`**.
- Every form is checked **in JavaScript and again in PHP**.
- Every POST form carries a **CSRF token**.
- Uploads are checked for **real file type and size** on the server.
- Admin pages call **`require_admin()`** before any output.

### Team and responsibilities

| Member | Areas |
|---|---|
| **Hagen Dickson** | Accounts and login, product catalogue, categories |
| **Josephine Allan** | 3D designer, customization and pricing |
| **Donna Omaboe** | Cart, checkout, Paystack payment |
| **Eyram Awoye** | Orders, delivery, admin dashboard |

---

## Run it locally (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Clone this repository into `C:\xampp\htdocs\`:
   ```bash
   git clone https://github.com/trpbtl/ecom-team-project.git
   ```
3. Open phpMyAdmin (`http://localhost/phpmyadmin`) and import `database/packe.sql`. This creates the `packe` database with starter products.
4. Optional: copy `core/local_cred.example.php` to `core/local_cred.php` and add your Paystack **test** keys. The database defaults (root, no password) already match XAMPP.
5. Open `http://localhost/ecom-team-project/`.

**Make an admin:** register an account on the site, then run this in phpMyAdmin:
```sql
UPDATE customers SET user_role = 1 WHERE email = 'you@example.com';   -- 1 = admin
UPDATE customers SET user_role = 3 WHERE email = 'rider@example.com'; -- 3 = delivery staff
```
Log out and in again for the new role to apply.

---

## Paystack test mode

1. Create a free Paystack account with **Ghana** as the country.
2. In **Settings → API Keys & Webhooks**, stay in **Test mode** and copy the `pk_test_…` and `sk_test_…` keys.
3. Put them in `core/local_cred.php` (locally) or in Render's environment variables (online).
4. At payment, use Paystack's test card or test mobile money from [Paystack's test payments page](https://paystack.com/docs/payments/test-payments). In test mode, Paystack pre-fills a test MTN number; click **Confirm**. No real money moves.

The server calls Paystack with PHP's `curl` extension when it's available, and otherwise with PHP's built-in HTTPS support. Some XAMPP setups start Apache without curl; payments still work either way.

How a payment is checked (`actions/process_payment_action.php` → `OrderController::completePayment`):
1. Our server creates the payment reference at checkout and remembers the exact amount.
2. After the popup, the server calls Paystack's **Verify API** with the secret key.
3. The order is saved only if the status is `success`, the reference matches, the currency is GHS, and the amount matches to the pesewa.
4. The order, its items, the payment and the delivery are saved in **one database transaction**, and the cart is emptied.

---

## Put it online for free (Render + Aiven)

1. **Database:** create a free **MySQL** service on [Aiven](https://aiven.io/free-tier). From its overview page, note the host, port, user and password, and download the **CA certificate** (`ca.pem`).
2. **Import the data** from a computer with XAMPP (it creates the `packe` database):
   ```bash
   C:\xampp\mysql\bin\mysql.exe -h YOUR-AIVEN-HOST -P YOUR-PORT -u avnadmin -p --ssl < database\packe.sql
   ```
   Type the Aiven password when asked.
3. **Web app:** on [Render](https://render.com), choose **New → Blueprint**, select this repository, and Render reads `render.yaml`. It builds from the `Dockerfile`.
4. In Render, fill in the environment variables:
   - `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`: from Aiven
   - `DB_NAME`: `packe`
   - `DB_SSL`: `true`
   - `DB_SSL_CA`: open `ca.pem` in Notepad and paste its whole text
   - `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`: your **test** keys
5. Open the `onrender.com` address Render gives you.
6. **After each deploy that changes the database**, open `/setup.php` on the site once. On a database that is already set up, it only adds what's missing (for example the `design_type` column) and never deletes data.

Notes on the free plans:
- Render's free service **sleeps after 15 minutes** without visits, and the first visit after that takes about a minute. Open the site a minute before a demo.
- Files uploaded on Render (product images and logos) are **lost when the service restarts or redeploys**, because its disk is temporary. That's fine for a demo; a real launch would store uploads in cloud storage.

---

## Documentation

- [System Development Lifecycle: Design and Architecture](docs/System-Development-Lifecycle-Design-and-Architecture.docx)
- Optional CMS version: https://josephineallan369.wixsite.com/packe-1
