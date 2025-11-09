# Symfony Blog API Demo

A demo blog API built with Symfony to practice REST API development, user authentication, and CRUD operations. This project is primarily for learning purposes.

## Features
- User registration and authentication using JWT
- Create, read, update, and delete blog posts
- Pagination and search for posts
- Unit and functional tests for services and controllers

## Requirements
- PHP 8.4.14
- Symfony 7.3.6
- Composer 2.8.12

## Installation

**1.** Clone the repository:
   ```bash
   git clone https://github.com/Kerliula/symfony-blog-api-demo.git
   cd symfony-blog-api-demo

**2.** Install dependencies:
  ```bash 
  composer install
  ```
**3.** Copy .env.example to .env and configure your database and secrets:
  ```bash
  cp .env.example .env
  ```

**4.** Create the database:
  ```bash
  php bin/console doctrine:database:create
  php bin/console doctrine:migrations:migrate
  ```

**5.** Start the local server:
  ```bash
  symfony server:start
  ```

## API Endpoints

- `POST /api/auth/signup` – Register a new user
- `POST /api/auth/signin` – Login (handled by json_login)
- `GET /api/posts` – List posts with optional pagination and search  
  - Query parameters:  
    - `page` (integer, optional) – page number, default is 1  
    - `limit` (integer, optional) – number of posts per page, default is 10  
    - `search` (string, optional) – search term to filter posts
- `GET /api/posts/{id}` – Get a single post
- `POST /api/posts` – Create a post (authenticated)
- `PUT /api/posts/update/{id}` – Update a post (authenticated)
- `DELETE /api/posts/{id}` – Delete a post (authenticated)

## License
This project is for learning purposes and is open for personal use.
