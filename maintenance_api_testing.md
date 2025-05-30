# Maintenance API Endpoints Testing Guide

This document provides instructions on how to test the maintenance API endpoints for the equipment maintenance system.

## Prerequisites

- The application should be running (using Docker or locally)
- You should have an admin account to access the protected routes
- You should have Postman, cURL, or another API testing tool

## Authentication

Before testing the maintenance endpoints, you need to authenticate:

```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@example.com", "password": "password"}'
```

Example response:
```json
{
  "user": {
    "id": 1,
    "full_name": "Admin User",
    "email": "admin@example.com",
    "role": "Admin"
  },
  "token": "1|abcdefghijklmnopqrstuvwxyz123456789"
}
```

Save the token for use in subsequent requests.

## 1. List All Maintenances

### Request

```bash
curl -X GET http://localhost:8080/api/maintenances \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### Response

```json
{
  "data": [
    {
      "id": 1,
      "equipment_id": 1,
      "maintenance_type": "Preventive",
      "scheduled_date": "2023-08-15",
      "performed_date": "2023-08-16",
      "next_maintenance_date": "2023-11-15",
      "observations": "Replaced air filters and updated firmware",
      "created_at": "2023-08-01T10:00:00.000000Z",
      "updated_at": "2023-08-16T15:30:00.000000Z",
      "equipment": {
        "id": 1,
        "name": "Server X1",
        "type": "Server",
        "status": "active"
      }
    }
  ]
}
```

## 2. Get Maintenance by ID

### Request

```bash
curl -X GET http://localhost:8080/api/maintenances/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### Response

```json
{
  "data": {
    "id": 1,
    "equipment_id": 1,
    "maintenance_type": "Preventive",
    "scheduled_date": "2023-08-15",
    "performed_date": "2023-08-16",
    "next_maintenance_date": "2023-11-15",
    "observations": "Replaced air filters and updated firmware",
    "created_at": "2023-08-01T10:00:00.000000Z",
    "updated_at": "2023-08-16T15:30:00.000000Z",
    "equipment": {
      "id": 1,
      "name": "Server X1",
      "type": "Server",
      "status": "active"
    }
  }
}
```

## 3. Create New Maintenance

### Request

```bash
curl -X POST http://localhost:8080/api/maintenances \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "equipment_id": 1,
    "maintenance_type": "Corrective",
    "scheduled_date": "2023-09-20",
    "performed_date": null,
    "next_maintenance_date": "2023-12-20",
    "observations": "Hard drive replacement needed",
    "technician_id": 1
  }'
```

### Response

```json
{
  "message": "Maintenance created successfully",
  "data": {
    "id": 2,
    "equipment_id": 1,
    "maintenance_type": "Corrective",
    "scheduled_date": "2023-09-20",
    "performed_date": null,
    "next_maintenance_date": "2023-12-20",
    "observations": "Hard drive replacement needed",
    "technician_id": 1,
    "created_at": "2023-09-01T14:25:00.000000Z",
    "updated_at": "2023-09-01T14:25:00.000000Z",
    "equipment": {
      "id": 1,
      "name": "Server X1",
      "type": "Server",
      "status": "active"
    },
    "technician": {
      "id": 1,
      "full_name": "Admin User"
    }
  }
}
```

## 4. Update Maintenance

### Request

```bash
curl -X PUT http://localhost:8080/api/maintenances/2 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "performed_date": "2023-09-21",
    "observations": "Hard drive replaced and system tested"
  }'
```

### Response

```json
{
  "message": "Maintenance updated successfully",
  "data": {
    "id": 2,
    "equipment_id": 1,
    "maintenance_type": "Corrective",
    "scheduled_date": "2023-09-20",
    "performed_date": "2023-09-21",
    "next_maintenance_date": "2023-12-20",
    "observations": "Hard drive replaced and system tested",
    "technician_id": 1,
    "created_at": "2023-09-01T14:25:00.000000Z",
    "updated_at": "2023-09-21T10:15:00.000000Z"
  }
}
```

## 5. Delete Maintenance

### Request

```bash
curl -X DELETE http://localhost:8080/api/maintenances/2 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Response

```json
{
  "message": "Maintenance deleted successfully"
}
```

## 6. Filter Maintenances by Equipment ID

### Request

```bash
curl -X GET "http://localhost:8080/api/maintenances?equipment_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

### Response

```json
{
  "data": [
    {
      "id": 1,
      "equipment_id": 1,
      "maintenance_type": "Preventive",
      "scheduled_date": "2023-08-15",
      "performed_date": "2023-08-16",
      "next_maintenance_date": "2023-11-15",
      "observations": "Replaced air filters and updated firmware",
      "technician_id": 1,
      "created_at": "2023-08-01T10:00:00.000000Z",
      "updated_at": "2023-08-16T15:30:00.000000Z",
      "equipment": {
        "id": 1,
        "name": "Server X1",
        "type": "Server",
        "status": "active"
      },
      "technician": {
        "id": 1,
        "full_name": "Admin User"
      }
    }
  ]
}
```

## Implementation Steps

To implement these endpoints, you need to:

1. Create a Maintenance model
2. Create a MaintenanceController
3. Register the routes in the API routes file
4. Test each endpoint using the examples above

## Notes

- All requests require authentication with an admin token
- The `performed_date` and `next_maintenance_date` fields are nullable
- The `technician_id` field references the `users` table
- The `equipment_id` field references the `equipments` table
