# AMT CRM Backend - API Documentation

## Overview

The AMT CRM Backend provides a comprehensive REST API for managing customer relationships, inventory, invoicing, and employee management.

## Base URL

```
HTTP: http://api.himmanav.com
```

## Authentication

The API uses Laravel Sanctum for authentication. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {your-token}
```

## Response Format

All API responses follow this standard format:

```json
{
    "status": true,
    "message": "Success message",
    "data": {
        // Response data
    }
}
```

## Error Responses

```json
{
    "status": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error"]
    }
}
```

## API Endpoints

### Authentication

#### Login
- **POST** `/api/v1/login`
- **Description**: Authenticate a regular user
- **Body**:
  ```json
  {
    "number": "1234567890",
    "password": "password"
  }
  ```

#### Company Login
- **POST** `/api/v1/c-login`
- **Description**: Authenticate a company user (admin/employee)
- **Body**:
  ```json
  {
    "number": "1234567890",
    "password": "password"
  }
  ```

#### Register
- **POST** `/api/v1/register`
- **Description**: Register a new user
- **Body**:
  ```json
  {
    "name": "John Doe",
    "email": "john@example.com",
    "number": "1234567890",
    "password": "password",
    "password_confirmation": "password"
  }
  ```

### Health Check

#### Application Health
- **GET** `/api/health`
- **Description**: Check application health status
- **Response**:
  ```json
  {
    "status": "healthy",
    "timestamp": "2025-01-13T10:00:00.000000Z",
    "version": "1.0.0",
    "environment": "production"
  }
  ```

### Users Management

#### Get Authenticated User
- **GET** `/api/v1/user`
- **Authentication**: Required
- **Description**: Get current user information

#### Get All Users
- **GET** `/api/v1/users`
- **Authentication**: Required
- **Permission**: `view users`
- **Description**: Get list of all users

#### Create User
- **POST** `/api/v1/users`
- **Authentication**: Required
- **Permission**: `add users`
- **Description**: Create a new user

### Companies

#### Get All Company Names
- **GET** `/api/v1/companies/names`
- **Description**: Get list of all company names (public)

#### Get Company Details
- **GET** `/api/v1/companies/{id}`
- **Authentication**: Required
- **Description**: Get specific company details

### Items & Inventory

#### Get All Items
- **GET** `/api/v1/items`
- **Authentication**: Required
- **Description**: Get list of all items

#### Create Item
- **POST** `/api/v1/items`
- **Authentication**: Required
- **Permission**: `add items`
- **Description**: Create a new item

#### Get Item
- **GET** `/api/v1/items/{id}`
- **Authentication**: Required
- **Description**: Get specific item details

### Invoices

#### Get All Invoices
- **GET** `/api/v1/invoices`
- **Authentication**: Required
- **Description**: Get list of all invoices

#### Create Invoice
- **POST** `/api/v1/invoices`
- **Authentication**: Required
- **Description**: Create a new invoice

#### Download Invoice PDF
- **GET** `/api/v1/invoices/{id}/download`
- **Authentication**: Required
- **Description**: Download invoice as PDF

### Tasks

#### Get All Tasks
- **GET** `/api/v1/tasks`
- **Authentication**: Required
- **Description**: Get list of all tasks

#### Create Task
- **POST** `/api/v1/tasks`
- **Authentication**: Required
- **Permission**: `add task`
- **Description**: Create a new task

#### Update Task
- **PUT** `/api/v1/tasks/{id}`
- **Authentication**: Required
- **Permission**: `update task`
- **Description**: Update a task

### Attendance

#### Record Attendance
- **POST** `/api/v1/attendance/record`
- **Authentication**: Required
- **Description**: Record daily attendance

#### Get Attendance
- **GET** `/api/v1/attendance/{id}`
- **Authentication**: Required
- **Description**: Get attendance records

### Categories

#### Get All Categories
- **GET** `/api/v1/categories`
- **Authentication**: Required
- **Description**: Get hierarchical category tree

#### Create Category
- **POST** `/api/v1/categories`
- **Authentication**: Required
- **Description**: Create a new category

## Rate Limiting

- **API endpoints**: 10 requests per second
- **Authentication endpoints**: 5 requests per minute

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Pagination

For endpoints that return lists, pagination is supported:

```json
{
    "data": [...],
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
}
```

## File Uploads

File uploads are supported for:
- Profile pictures
- Item images
- Invoice attachments
- Business proof documents

Maximum file size: 5MB
Supported formats: JPG, PNG, PDF

## Webhooks

Currently, webhooks are not implemented but can be added for:
- Payment confirmations
- Task assignments
- Attendance alerts
- Invoice generation

## SDKs & Libraries

Official SDKs are not yet available, but the API follows REST standards and can be consumed by any HTTP client.

## Support

For API support and questions:
- Email: support@himmanav.com
- Documentation: https://docs.himmanav.com
- Status page: https://status.himmanav.com 