# Contributing to Reconcile AI

Thank you for your interest in contributing to Reconcile AI! We appreciate your help in making this project better. Please follow the guidelines below to ensure a smooth contribution process.

## 📌 Table of Contents
- [Getting Started](#getting-started)
- [Code of Conduct](#code-of-conduct)
- [How to Contribute](#how-to-contribute)
- [Code Style](#code-style)
- [Commit Message Guidelines](#commit-message-guidelines)
- [Pull Request Process](#pull-request-process)
- [Reporting Issues](#reporting-issues)
- [Security Issues](#security-issues)

## 🚀 Getting Started
1. Fork the repository.
2. Clone your fork:
   ```sh
   git clone https://github.com/hngprojects/Reconcile-AI-BE.git
   ```
3. Navigate into the project directory:
   ```sh
   cd Reconcile-AI-BE/
   ```
4. Install dependencies:
   ```sh
   composer install
   ```
5. Copy the environment file and configure it:
   ```sh
   cp .env.example .env
   php artisan key:generate
   ```
6. Set up the database and run migrations:
   ```sh
   php artisan migrate --seed
   ```
7. Run the development server:
   ```sh
   php artisan serve
   ```
## 💡 How to Contribute
- **Bug Fixes**: Search for open issues tagged with `bug` and submit a fix.
- **New Features**: Propose new features via an issue before implementing.
- **Documentation**: Help improve our docs where necessary.
- **Code Refactoring**: Improve existing code without changing functionality.

## 🔄 Commit Message Guidelines
Use the following format for commit messages:
```
[type]: Short description

Detailed explanation (if necessary)
```
Example:
```
feat: add file upload functionality

Added support for CSV file uploads with validation.
```
Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests

## 🔀 Pull Request Process
1. Ensure your code follows the style guidelines.
2. Create a new branch (`feature-branch` or `fix-branch`).
3. Push your changes and open a pull request.
4. Fill in the PR template and link related issues.
5. Wait for reviews and make necessary changes.
6. Once approved, your PR will be merged.

## 🐞 Reporting Issues
If you find a bug, please open an issue with:
- A clear and descriptive title.
- Steps to reproduce the issue.
- Expected vs. actual behavior.
- Any relevant logs or screenshots.

Thank you for contributing! 🚀

