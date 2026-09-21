<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <meta name="description" content="Privacy Policy - MONTERA">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="shortcut icon" type="image/png" href="<?= base_url() ?>assets/img/fav.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: #f8f9fa;
      color: #333;
      line-height: 1.7;
    }

    .pp-header {
      background: linear-gradient(135deg, #012062 0%, #1a3a8a 100%);
      color: #fff;
      padding: 60px 0 40px;
      text-align: center;
    }

    .pp-header img {
      height: 50px;
      margin-bottom: 20px;
    }

    .pp-header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .pp-header p {
      font-size: 0.95rem;
      opacity: 0.8;
    }

    .pp-content {
      max-width: 800px;
      margin: -30px auto 40px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
      padding: 40px 48px;
    }

    .pp-content h2 {
      font-size: 1.25rem;
      font-weight: 600;
      color: #012062;
      margin-top: 32px;
      margin-bottom: 12px;
      padding-bottom: 8px;
      border-bottom: 2px solid #e8ecf1;
    }

    .pp-content h2:first-child {
      margin-top: 0;
    }

    .pp-content p,
    .pp-content li {
      font-size: 0.95rem;
      color: #555;
    }

    .pp-content ul {
      padding-left: 20px;
      margin-bottom: 16px;
    }

    .pp-content ul li {
      margin-bottom: 6px;
    }

    .pp-footer {
      text-align: center;
      padding: 24px 0;
      font-size: 0.85rem;
      color: #999;
    }

    .pp-back {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: rgba(255, 255, 255, 0.8);
      text-decoration: none;
      font-size: 0.9rem;
      margin-bottom: 16px;
      transition: color 0.2s;
    }

    .pp-back:hover {
      color: #fff;
    }

    @media (max-width: 768px) {
      .pp-content {
        margin: -20px 16px 30px;
        padding: 28px 24px;
      }

      .pp-header {
        padding: 40px 20px 30px;
      }

      .pp-header h1 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>

<body>

  <div class="pp-header">
    <a href="<?= base_url() ?>" class="pp-back">&larr; Back to Home</a>
    <div>
      <img src="<?= base_url() ?>assets/img/logo.png" alt="MONTERA Logo">
    </div>
    <h1>Privacy Policy</h1>
    <p>Last updated: April 1, 2026</p>
  </div>

  <div class="pp-content">

    <h2>1. Introduction</h2>
    <p>
      MONTERA operates an internal e-commerce and business management platform
      that integrates with third-party marketplace platforms including Shopee, Lazada, TikTok Shop,
      and WhatsApp Business. This application is used internally by MONTERA employees and authorized
      personnel for business operations purposes.
    </p>
    <p>
      This Privacy Policy describes how we collect, use, store, share, and protect data
      obtained through our internal application and third-party platform API integrations.
      By using this application, you consent to the practices described in this Privacy Policy.
    </p>

    <h2>2. Data We Collect</h2>
    <p>Through our platform and third-party API integrations, we collect and process the following data:</p>
    <ul>
      <li><strong>Employee Account Data:</strong> Name, email address, phone number, role, position, and login credentials for internal staff access.</li>
      <li><strong>Marketplace Order Data:</strong> Order details, product information, shipping addresses, payment status, and fulfillment data synced from Shopee, Lazada, and TikTok Shop via their official APIs.</li>
      <li><strong>Product & Inventory Data:</strong> Product listings, inventory levels, pricing, SKU information, and catalog data from connected marketplace stores.</li>
      <li><strong>Customer Data:</strong> Buyer names, shipping addresses, phone numbers, and order history associated with marketplace orders.</li>
      <li><strong>API Access Tokens:</strong> OAuth 2.0 access tokens and refresh tokens required to maintain authorized connections with marketplace platform APIs.</li>
      <li><strong>Advertising & Analytics Data:</strong> Campaign performance metrics, advertising spend data, sales analytics, and financial reports from Shopee, TikTok, and Meta platforms.</li>
      <li><strong>Influencer & Campaign Data:</strong> Influencer profiles, campaign performance, endorsement details, and commission data.</li>
      <li><strong>Communication Data:</strong> Customer messages and notifications processed through WhatsApp Business API.</li>
    </ul>

    <h2>3. How We Use the Data</h2>
    <p>All data collected is used strictly for internal business operations:</p>
    <ul>
      <li>Synchronizing and managing orders across Shopee, Lazada, and TikTok Shop from a centralized dashboard.</li>
      <li>Processing shipping, fulfillment, printing shipping labels, and logistics operations.</li>
      <li>Managing product catalog, inventory levels, and pricing across multiple marketplaces.</li>
      <li>Generating internal sales reports, revenue analytics, and business performance dashboards.</li>
      <li>Managing influencer marketing campaigns, tracking endorsements, and calculating commissions.</li>
      <li>Managing customer relationships, support tickets, and communication via WhatsApp Business.</li>
      <li>Managing advertising campaigns and tracking ad performance across platforms.</li>
      <li>Internal employee management including HR operations, onboarding, and performance tracking.</li>
      <li>Authenticating internal users and maintaining secure role-based access control.</li>
    </ul>

    <h2>4. Third-Party Platform Integrations</h2>
    <p>Our application integrates with the following third-party platforms via their official APIs:</p>
    <ul>
      <li><strong>Shopee Open Platform</strong> — Order sync, product management, shipping operations, sales analytics, and advertising campaign data.</li>
      <li><strong>Lazada Open Platform</strong> — Order processing, product catalog sync, shipping document generation, and financial reporting.</li>
      <li><strong>TikTok Shop Open API</strong> — Order fulfillment, product management, shipping packages, campaign analytics, GMV tracking, and advertising data.</li>
      <li><strong>WhatsApp Business API</strong> — Customer communication, order notifications, and support messaging.</li>
      <li><strong>Meta Marketing API</strong> — Advertising campaign management and performance analytics.</li>
    </ul>
    <p>
      All integrations use official OAuth 2.0 authorization flows provided by each platform.
      We only request and access API scopes that are necessary for the business operations described above.
      Access tokens are stored securely on our servers and refreshed automatically according to each platform's token lifecycle requirements.
    </p>
    <p>
      Data retrieved from these platforms is used solely for internal business management
      and is not shared with, sold to, or made accessible to any unauthorized third parties.
    </p>

    <h2>5. Data Storage and Security</h2>
    <p>We implement appropriate technical and organizational measures to protect all data:</p>
    <ul>
      <li>All API tokens and sensitive credentials are stored with encryption on secure servers.</li>
      <li>Application access is restricted to authorized MONTERA employees only.</li>
      <li>Role-Based Access Control (RBAC) system ensures employees only access data relevant to their role and responsibilities.</li>
      <li>Session-based authentication with secure session management and automatic timeout.</li>
      <li>Database access is protected with parameterized queries to prevent SQL injection.</li>
      <li>CSRF protection and input validation on all form submissions.</li>
      <li>Regular security updates and server maintenance.</li>
    </ul>

    <h2>6. Data Sharing and Disclosure</h2>
    <p>
      This is an internal business application. We do not sell, rent, trade, or share
      data with unauthorized third parties. Data is shared only in the following circumstances:
    </p>
    <ul>
      <li><strong>With Marketplace Platforms:</strong> Sending order status updates, shipping information, and product data back to Shopee, Lazada, and TikTok Shop as part of normal marketplace operations.</li>
      <li><strong>With WhatsApp Business:</strong> Sending customer notifications and support messages through the official API.</li>
      <li><strong>With Hosting/Infrastructure Providers:</strong> Data is stored on servers managed by our hosting provider, bound by service agreements.</li>
      <li><strong>For Legal Compliance:</strong> When required by Indonesian law, regulation, or governmental request.</li>
    </ul>

    <h2>7. Data Retention</h2>
    <p>
      Employee account data is retained for the duration of employment and removed upon offboarding.
      Marketplace transaction and order data is retained for business reporting, accounting,
      and legal compliance purposes as required by applicable Indonesian regulations.
      API tokens are retained only while the platform connection is active and are revoked upon disconnection.
    </p>

    <h2>8. User Rights</h2>
    <p>Internal users (employees) have the right to:</p>
    <ul>
      <li><strong>Access</strong> — View personal data stored in the system through their profile.</li>
      <li><strong>Correction</strong> — Request correction of inaccurate personal information.</li>
      <li><strong>Deletion</strong> — Request removal of personal data upon leaving the organization.</li>
    </ul>
    <p>
      Marketplace customers whose data is processed through our platform may exercise their
      data rights directly through the respective marketplace platform (Shopee, Lazada, TikTok Shop)
      where their data originates.
    </p>

    <h2>9. Cookies and Session Data</h2>
    <p>
      This application uses essential cookies and server-side sessions for user authentication
      and maintaining application state. No third-party tracking, advertising, or analytics
      cookies are used.
    </p>

    <h2>10. Changes to This Policy</h2>
    <p>
      We may update this Privacy Policy to reflect changes in our data practices or
      applicable regulations. Changes will be published on this page with an updated
      revision date. Continued use of the application constitutes acceptance of the revised policy.
    </p>

    <h2>11. Contact Us</h2>
    <p>
      For questions or concerns regarding this Privacy Policy or our data practices, contact us at:
    </p>
    <ul>
      <li><strong>Email:</strong> support@Montera.co.id</li>
      <li><strong>Website:</strong> <a href="https://Montera.co.id" target="_blank">Montera.co.id</a></li>
    </ul>

  </div>

  <div class="pp-footer">
    &copy; <?= date('Y') ?> MONTERA. All rights reserved.
  </div>

</body>

</html>
