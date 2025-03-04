# Reconcile Project Setup

## Introduction
This is a Laravel project that includes JWT authentication and Swagger API documentation. This guide will walk you through setting up the project and ensuring all dependencies are installed properly.

---

## Prerequisites
Ensure you have the following installed on your system:
- PHP 8.2+
- Composer
- MySQL or SQLite (for database)
- Node.js & npm (for frontend assets, if applicable)

---

## Installation Steps

### 1. Clone the Repository
```bash
    git clone <repository-url>
    cd <project-folder>
```

### 2. Install Dependencies
```bash
    composer install
    npm install
```

### 3. Configure Environment
```bash
    cp .env.example .env
```
Update the `.env` file with your database and application settings.

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

### Update Swagger Documentation
```bash
    php artisan l5-swagger:generate
```

---

## Contribution Guidelines
1. Fork the repository and create a feature branch.
2. Follow PSR-4 coding standards.
3. Ensure all new code includes tests.
4. Run `composer test` before committing changes.

---

## Troubleshooting

### Common Issues:
1. **Missing JWT secret:** Run `php artisan jwt:secret --force`.
2. **Swagger docs not showing:** Run `php artisan l5-swagger:generate`.
3. **Database issues:** Ensure `.env` has correct database credentials and run migrations again.

For further support, please open an issue in the repository.

---

## License
This project is licensed under the MIT License.