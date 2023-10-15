# TODO API

This is a TODO API application written in Laravel 10. It provides various features, including basic CRUD operations, support for task nesting, filtering, user authentication and registration, API protection using access tokens, and user rights management for task operations.

## Getting Started

Follow the steps below to set up and run the application on your local environment.

1. Install PHP dependencies using Composer:

   ```bash
   composer install

2. Create .env file in the root directory and set your database credentials:

    ```dotenv
    DB_CONNECTION=mysql
    DB_HOST={your host}
    DB_PORT={port}
    DB_DATABASE={DB name}
    DB_USERNAME={userName}
    DB_PASSWORD={password}

3. Run database migrations to create the required tables:
   ```bash
   php artisan migrate

4. Start the Laravel development server:
   ```bash
   php artisan serve

Your application should now be running at http://localhost:8000.

## Postman Collection
We provide a ready-to-use Postman collection for testing the API. To perform requests, 
you need to obtain an authentication token through the ``/login`` endpoint. Then, use 
this token for making requests. Set the ``"Authorization"`` header to ``'Bearer Token'`` and 
provide the previously obtained token value.


## API Endpoints

- GET /tasks: Get a list of tasks.
- POST /tasks/create: Create a new task.
- PUT /tasks/{taskId}: Update a specific task by ID.
- DELETE /tasks/{taskId}: Delete a specific task by ID.
- 
Please use the Postman collection ``(todo api.postman_collection.json)`` 
for detailed API documentation and request examples.

Note: This README provides a basic setup guide.








