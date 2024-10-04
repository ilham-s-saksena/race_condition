# Laravel Race Condition Demo

This repository contains a demo application built with Laravel that demonstrates race conditions in web applications, especially during critical operations like inventory management and concurrent checkouts.

## Table of Contents
- [Laravel Race Condition Demo](#laravel-race-condition-demo)
  - [Table of Contents](#table-of-contents)
  - [Introduction](#introduction)
    - [Features](#features)
  - [Requirements](#requirements)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Understanding Race Conditions](#understanding-race-conditions)
  - [Example Scenario](#example-scenario)
  - [Solution](#solution)
  - [Contributing](#contributing)

## Introduction

A **race condition** occurs when multiple processes access and modify shared data concurrently, and the outcome depends on the order in which the processes are executed. This project simulates such race conditions during a product checkout process when stock levels are limited.

### Features
- Simulates race condition scenarios with concurrent checkout requests.
- Demonstrates potential issues with concurrent requests accessing shared resources.
- Provides examples of how to mitigate race conditions in Laravel applications using database transactions and locks.

## Requirements

- PHP >= 8.3
- Composer
- Laravel >= 11.x
- MySQL or PostgreSQL (for proper transaction support)

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/ilham-s-saksena/race_condition.git
   cd race_condition
   ```
2. Install dependencies via Composer:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and configure your database and other necessary environment variables:
   ```bash
   cp .env.example .env
   ```
4. Generate the application key:
   ```bash
   php artisan key:generate
   ```
5. Run database migrations and seed the database with test data:
   ```bash
   php artisan migrate --seed
   ```

## Usage

1. Start the local development server:
   ```bash
   php artisan serve
   ```
2. In your browser, go to `http://localhost:8000` and explore the application. To simulate a race condition, you can use tools like Postman or concurrency testing tools (e.g., Artillery, Siege) to send multiple concurrent requests to the checkout endpoint.
3. Monitor the behavior of the application as multiple users attempt to check out a limited quantity of items simultaneously.
4. Or you can test the code with the PHPUnit i've been build at `tests/Feature/CheckoutTest.php` by run this command:
   ```bash
   php artisan test --filter=CheckoutTest
   ```

## Understanding Race Conditions
A race condition occurs when two or more processes are attempting to modify shared data at the same time, leading to unexpected behavior or inconsistencies. In web applications, this often happens when:

- Multiple users are trying to update the same record (e.g., updating stock levels).
- Operations that need to be atomic (e.g., deducting stock) are not properly synchronized.
This repository simulates race conditions during a checkout process where several users attempt to purchase a product with limited stock. Without proper handling, two or more users could succeed in purchasing the product even when only one unit is available.

## Example Scenario
1. User A and User B both view a product with 1 item in stock.
2. Both users proceed to checkout at nearly the same time.
3. Without protection against race conditions, both users might be able to purchase the item, even though there is only 1 available.

## Solution
To mitigate race conditions, this demo explores the following techniques:

1. **Database Transactions** 
   Ensures that a series of database operations either all succeed or all fail, preventing inconsistent state.

2. **Optimistic Locking** 
   Uses a version field in the database to detect concurrent modifications and prevent data overwrites.

3. **Pessimistic Locking** *(not implemeted yet)*
    Acquires a database lock on the record before performing updates to ensure only one process can modify it at a time.

4. **Laravel Queues** *(not implemeted yet)*
    Offloads the processing of concurrent requests to a queue system, ensuring that requests are handled sequentially.

Check out the `app/Http/Controllers/CheckoutController.php` for detailed implementation of these solutions.

## Contributing
Contributions are welcome! Please open an issue or submit a pull request with any improvements or suggestions.