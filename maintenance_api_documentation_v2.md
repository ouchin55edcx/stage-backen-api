# Maintenance API Documentation v2.0

This document provides comprehensive documentation for the Maintenance API endpoints with the corrected relationship structure. Maintenance records are now linked to Interventions instead of Equipment directly.

## Overview

The Maintenance system manages maintenance records that are associated with interventions. Each maintenance record tracks:
- The intervention it relates to (which in turn relates to equipment)
- Type of maintenance performed
- Scheduled and performed dates
- Observations and notes

## Relationship Structure

```
Equipment -> Intervention -> Maintenance
```

- **Equipment** has many **Interventions**
- **Intervention** has many **Maintenances**
- **Maintenance** belongs to one **Intervention**

## Authentication

All endpoints require authentication with an admin token. Include the token in the Authorization header:

```
Authorization: Bearer YOUR_TOKEN_HERE
```

## API Endpoints

### 1. List All Maintenances

**GET** `/api/maintenances`

Retrieves all maintenance records with their related intervention and equipment data.

#### Query Parameters
- `intervention_id` (optional): Filter maintenances by intervention ID

#### Example Request

```bash
curl -X GET http://localhost:8080/api/maintenances \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

#### Example Response

```json
{
  "data": [
    {
      "id": 1,
      "intervention_id": 1,
      "maintenance_type": "Preventive",
      "scheduled_date": "2024-01-15",
      "performed_date": "2024-01-16",
      "next_maintenance_date": "2024-04-15",
      "observations": "Replaced air filters and updated firmware",
      "created_at": "2024-01-01T10:00:00.000000Z",
      "updated_at": "2024-01-16T15:30:00.000000Z",
      "intervention": {
        "id": 1,
        "date": "2024-01-10T09:00:00.000000Z",
        "technician_name": "John Doe",
        "note": "Regular maintenance intervention",
        "equipment_id": 1,
        "equipment": {
          "id": 1,
          "name": "Server X1",
          "type": "Server",
          "status": "active",
          "ip_address": "192.168.1.100"
        }
      }
    }
  ]
}
```

### 2. Get Maintenance by ID

**GET** `/api/maintenances/{id}`

Retrieves a specific maintenance record by its ID.

#### Example Request

```bash
curl -X GET http://localhost:8080/api/maintenances/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

#### Example Response

```json
{
  "data": {
    "id": 1,
    "intervention_id": 1,
    "maintenance_type": "Preventive",
    "scheduled_date": "2024-01-15",
    "performed_date": "2024-01-16",
    "next_maintenance_date": "2024-04-15",
    "observations": "Replaced air filters and updated firmware",
    "created_at": "2024-01-01T10:00:00.000000Z",
    "updated_at": "2024-01-16T15:30:00.000000Z",
    "intervention": {
      "id": 1,
      "date": "2024-01-10T09:00:00.000000Z",
      "technician_name": "John Doe",
      "note": "Regular maintenance intervention",
      "equipment_id": 1,
      "equipment": {
        "id": 1,
        "name": "Server X1",
        "type": "Server",
        "status": "active",
        "ip_address": "192.168.1.100"
      }
    }
  }
}
```

### 3. Create New Maintenance

**POST** `/api/maintenances`

Creates a new maintenance record linked to an intervention.

#### Required Fields
- `intervention_id`: ID of the intervention (required)
- `maintenance_type`: Type of maintenance (required)
- `scheduled_date`: Planned date for maintenance (required)

#### Optional Fields
- `performed_date`: Date when maintenance was actually performed
- `next_maintenance_date`: Date for next scheduled maintenance
- `observations`: Notes and observations

#### Example Request

```bash
curl -X POST http://localhost:8080/api/maintenances \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "intervention_id": 1,
    "maintenance_type": "Corrective",
    "scheduled_date": "2024-02-20",
    "performed_date": null,
    "next_maintenance_date": "2024-05-20",
    "observations": "Hard drive replacement needed"
  }'
```

#### Example Response

```json
{
  "message": "Maintenance created successfully",
  "data": {
    "id": 2,
    "intervention_id": 1,
    "maintenance_type": "Corrective",
    "scheduled_date": "2024-02-20",
    "performed_date": null,
    "next_maintenance_date": "2024-05-20",
    "observations": "Hard drive replacement needed",
    "created_at": "2024-02-01T14:25:00.000000Z",
    "updated_at": "2024-02-01T14:25:00.000000Z",
    "intervention": {
      "id": 1,
      "date": "2024-01-10T09:00:00.000000Z",
      "technician_name": "John Doe",
      "note": "Regular maintenance intervention",
      "equipment_id": 1,
      "equipment": {
        "id": 1,
        "name": "Server X1",
        "type": "Server",
        "status": "active",
        "ip_address": "192.168.1.100"
      }
    }
  }
}
```

### 4. Update Maintenance

**PUT** `/api/maintenances/{id}`

Updates an existing maintenance record.

#### Example Request

```bash
curl -X PUT http://localhost:8080/api/maintenances/2 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "performed_date": "2024-02-21",
    "observations": "Hard drive replaced and system tested successfully"
  }'
```

#### Example Response

```json
{
  "message": "Maintenance updated successfully",
  "data": {
    "id": 2,
    "intervention_id": 1,
    "maintenance_type": "Corrective",
    "scheduled_date": "2024-02-20",
    "performed_date": "2024-02-21",
    "next_maintenance_date": "2024-05-20",
    "observations": "Hard drive replaced and system tested successfully",
    "created_at": "2024-02-01T14:25:00.000000Z",
    "updated_at": "2024-02-21T10:15:00.000000Z",
    "intervention": {
      "id": 1,
      "date": "2024-01-10T09:00:00.000000Z",
      "technician_name": "John Doe",
      "note": "Regular maintenance intervention",
      "equipment_id": 1,
      "equipment": {
        "id": 1,
        "name": "Server X1",
        "type": "Server",
        "status": "active",
        "ip_address": "192.168.1.100"
      }
    }
  }
}
```

### 5. Delete Maintenance

**DELETE** `/api/maintenances/{id}`

Deletes a maintenance record.

#### Example Request

```bash
curl -X DELETE http://localhost:8080/api/maintenances/2 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

#### Example Response

```json
{
  "message": "Maintenance deleted successfully"
}
```

### 6. Filter Maintenances by Intervention

**GET** `/api/maintenances?intervention_id={intervention_id}`

Retrieves all maintenance records for a specific intervention.

#### Example Request

```bash
curl -X GET "http://localhost:8080/api/maintenances?intervention_id=1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json"
```

#### Example Response

```json
{
  "data": [
    {
      "id": 1,
      "intervention_id": 1,
      "maintenance_type": "Preventive",
      "scheduled_date": "2024-01-15",
      "performed_date": "2024-01-16",
      "next_maintenance_date": "2024-04-15",
      "observations": "Replaced air filters and updated firmware",
      "created_at": "2024-01-01T10:00:00.000000Z",
      "updated_at": "2024-01-16T15:30:00.000000Z",
      "intervention": {
        "id": 1,
        "date": "2024-01-10T09:00:00.000000Z",
        "technician_name": "John Doe",
        "note": "Regular maintenance intervention",
        "equipment_id": 1,
        "equipment": {
          "id": 1,
          "name": "Server X1",
          "type": "Server",
          "status": "active",
          "ip_address": "192.168.1.100"
        }
      }
    }
  ]
}
```

## Validation Rules

### Create Maintenance
- `intervention_id`: required, must exist in interventions table
- `maintenance_type`: required, string, max 255 characters
- `scheduled_date`: required, valid date
- `performed_date`: optional, valid date
- `next_maintenance_date`: optional, valid date
- `observations`: optional, string

### Update Maintenance
- `intervention_id`: optional, must exist in interventions table
- `maintenance_type`: optional, string, max 255 characters
- `scheduled_date`: optional, valid date
- `performed_date`: optional, valid date
- `next_maintenance_date`: optional, valid date
- `observations`: optional, string

## Error Responses

### Validation Error (422)

```json
{
  "message": "Validation failed",
  "errors": {
    "intervention_id": [
      "The intervention id field is required."
    ],
    "maintenance_type": [
      "The maintenance type field is required."
    ]
  }
}
```

### Not Found Error (404)

```json
{
  "message": "No query results for model [App\\Models\\Maintenance] 999"
}
```

### Unauthorized Error (401)

```json
{
  "message": "Unauthenticated."
}
```

## Testing Steps

1. **Authentication**: First, obtain an admin token by logging in
2. **Create an Intervention**: Ensure you have an intervention to link the maintenance to
3. **Create Maintenance**: Use the intervention ID to create a new maintenance record
4. **Test CRUD Operations**: Test all create, read, update, and delete operations
5. **Test Filtering**: Test filtering maintenances by intervention ID
6. **Verify Relationships**: Ensure the response includes proper intervention and equipment data

## Notes

- All requests require authentication with an admin token
- Maintenance records are now linked to interventions, not directly to equipment
- The response includes nested intervention and equipment data for complete context
- The `performed_date` and `next_maintenance_date` fields are nullable
- The `intervention_id` field references the `interventions` table

## Migration Instructions

If you have existing maintenance data linked to equipment, you'll need to:

1. Run the migration to update the database structure
2. Manually migrate existing data by creating interventions for each equipment-maintenance relationship
3. Update the maintenance records to reference the new intervention IDs

## API Version

**Current Version**: v2.0
**Base URL**: `http://localhost:8080/api`
**Authentication**: Bearer Token Required
**Content-Type**: `application/json`