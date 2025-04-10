# Reconcile AI API ~ An AI-Powered Financial Reconciliation

Reconcile AI is an easy-to-use reconciliation platform (web app) for comparing any two sets of financial records, like customer records (e.g., student payments, inventory sales, transaction alerts), with bank statements for any discrepancies or irregularities. It offers a simple file upload interface, AI-based matching algorithms with manual overrides, and multiple options for exporting results, thereby decreasing manual interventions by the users while increasing efficiency and accuracy.

## 📑 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
- [Project Structure](#project-structure)
- [Contributing](#contributing)

## <span id="features"> ✨ Core Functionality </span>

- **User Onboarding and Authentication:** Give users an excellent onboarding experience so that they may easily learn and use the product.

- **Reconciliation:** Identify discrepancies with high precision, minimizing the risk of errors in financial reporting by utilizing AI-based matching algorithms.

- **Flexibility:** Provides manual override options that would allow its users to tackle complex or peculiar cases and provide accuracy in reconciliations.

- **Optimize User Experience:** Provides an easy-to-use file upload interface for users.

- **Results Export:** Ensures users can export reconciliation results for reports and further analysis.

## <span id="tech-stack">🛠️ Tech Stack</span>

- **Framework**: Laravel v11.6.1
- **Database**: PostgreSQL

## <span id="getting-started"> 🚀 Getting Started </span>

### Prerequisites

- PHP (v8.1^)
- Package manager: **composer**

### Installation

1. Clone the repository:

```bash
git clone https://github.com/hngprojects/Reconcile-AI-BE/
```

2. Navigate to the project directory:

```bash
cd Reconcile-AI-BE/
```

3. Install dependencies:

```bash
composer install
```


```bash
php artisan install:api
```


4. Run the development server:

```bash
composer run dev
```

### 3. Configure Environment
```bash
    cp .env.example .env
```
Update the `.env` file with your database and application settings.

---

### Update Swagger Documentation


Publish JWT & Swagger configuration files (only needed for first-time setup)
```bash
    php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
```
```bash
    php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"
```

Generate API documentation
```bash
    php artisan l5-swagger:generate
```

---
## Running the Application

### Start the Development Server
```bash
    php artisan serve
```

## API Documentation

This project includes API documentation using Swagger. After starting the application, visit:
```
    http://127.0.0.1:8000/api/docs
```

You can test API endpoints directly from Swagger using the **Try it out** feature.

---

## Additional Commands

### Run Tests
```bash
    php artisan test
```

---

5. Open [http://localhost:3000](http://localhost:8000) in your browser to see the app.

## <span id="project-structure">📂 Project Structure</span>

```plaintext
Reconcile-AI-BE/
│── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/
│   │   │   ├── Web/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   ├── Resources/
│   ├── Models/
│   ├── Providers/
│   ├── Repositories/
│   │   ├── Contracts/     # Interfaces for repositories
│   │   ├── Eloquent/      # Eloquent-based implementations
│   │   ├── BaseRepository.php
│   ├── Services/          # Business logic layer
│   ├── Helpers/           # Custom helper functions
│
│── bootstrap/
│── config/
│── database/
│   ├── factories/
│   ├── migrations/
│   ├── seeders/
│
│── public/
│── resources/
│   ├── js/                
│   ├── views/             
│   ├── css/              
│
│── routes/
│   ├── api.php
│   ├── web.php
│   ├── console.php
│
│── storage/
│── tests/
│── vendor/
│── .env
│── composer.json
│── package.json
│── artisan
│── composer.lock
│── phpunit.xml
│── postcss.config.js
│── tailwind.config.js
│── vite.config.js

```

## <span id="contributing">Interested in Contribution to ReconcileAI?</span>
Please review our [Contribution Guidelines](./CONTRIBUTING.md)

