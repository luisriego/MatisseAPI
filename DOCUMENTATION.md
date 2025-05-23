# Admicon Matisse - Recurring Expense Manager

A Symfony-based web application designed to manage and track recurring expenses. This application features an admin interface for creating, viewing, updating, and deleting recurring expenses and their types.

## Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Running the Application](#running-the-application)
    - [Development](#development)
    - [Production](#production)
- [Database](#database)
    - [Migrations](#migrations)
    - [Fixtures](#fixtures)
- [Running Tests](#running-tests)
- [Project Structure (Key Directories)](#project-structure-key-directories)
- [Built With](#built-with)
- [Contributing](#contributing)
- [License](#license)

## Features

-   **User Management:** Secure admin area with role-based access (e.g., `ROLE_ADMIN`).
-   **Expense Type Management:**
    -   CRUD operations for expense types.
    -   Flag expense types as suitable for recurring expenses (`isRecurring` property).
-   **Recurring Expense Management:**
    -   CRUD operations for recurring expenses.
    -   Association with specific, pre-defined expense types (filtered by `isRecurring=true`).
    -   Define properties such as description, amount, frequency (e.g., MONTHLY, ANNUALLY), due day, specific months of occurrence, start date, end date, and remaining occurrences.
    -   Toggle active/inactive status for recurring expenses.
    -   Optional notes for each recurring expense.
-   **Admin Interface:** User-friendly interface for managing all aspects of the application.

## Prerequisites

Before you begin, ensure you have met the following requirements:

-   PHP `[Your PHP Version, e.g., 8.1 or higher]`
-   Composer `[Your Composer Version, or latest]`
-   Symfony CLI (recommended for local development)
-   SQLite3 (or your chosen database system if different)
-   Node.js and npm (or Yarn) for frontend asset management (if using Webpack Encore)
-   A web server (like Nginx or Apache) for production, or use Symfony's built-in server for development.

## Installation

To install Admicon Matisse, follow these steps:

1.  **Clone the repository:**
