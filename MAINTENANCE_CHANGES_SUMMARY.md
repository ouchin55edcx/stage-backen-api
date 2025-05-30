# Maintenance Model Changes Summary

## What Was Changed

The Maintenance model has been corrected to have a relationship with **Intervention** instead of **Equipment**. This change creates a more logical data structure where:

- Equipment → Intervention → Maintenance

## Files Modified

### 1. Database Migration
- **File**: `database/migrations/2025_05_20_000000_create_maintenances_table.php`
- **Change**: Changed `equipment_id` foreign key to `intervention_id`

### 2. New Migration Created
- **File**: `database/migrations/2025_01_20_120000_update_maintenances_table_change_equipment_to_intervention.php`
- **Purpose**: Updates existing database structure to change from equipment_id to intervention_id

### 3. Maintenance Model
- **File**: `app/Models/Maintenance.php`
- **Changes**:
  - Updated `$fillable` array to use `intervention_id` instead of `equipment_id`
  - Changed relationship method from `equipment()` to `intervention()`
  - Now returns `belongsTo(Intervention::class)`

### 4. Intervention Model
- **File**: `app/Models/Intervention.php`
- **Changes**:
  - Added `maintenances()` relationship method
  - Returns `hasMany(Maintenance::class)`

### 5. MaintenanceController
- **File**: `app/Http/Controllers/MaintenanceController.php`
- **Changes**:
  - Updated all methods to use `intervention_id` instead of `equipment_id`
  - Changed validation rules to validate `intervention_id`
  - Updated eager loading to use `intervention.equipment` relationship
  - Updated filtering to filter by `intervention_id`

### 6. API Documentation
- **File**: `maintenance_api_documentation_v2.md`
- **Purpose**: Complete API documentation with corrected relationships and examples

## New Relationship Structure

```
Equipment (1) → (Many) Intervention (1) → (Many) Maintenance
```

### Before:
```php
// Maintenance belonged directly to Equipment
$maintenance->equipment_id = 1;
$maintenance->equipment; // Direct relationship
```

### After:
```php
// Maintenance belongs to Intervention, which belongs to Equipment
$maintenance->intervention_id = 1;
$maintenance->intervention; // Intervention relationship
$maintenance->intervention->equipment; // Equipment through intervention
```

## API Changes

### Request Structure (Before)
```json
{
  "equipment_id": 1,
  "maintenance_type": "Preventive",
  "scheduled_date": "2024-01-15"
}
```

### Request Structure (After)
```json
{
  "intervention_id": 1,
  "maintenance_type": "Preventive",
  "scheduled_date": "2024-01-15"
}
```

### Response Structure (After)
```json
{
  "data": {
    "id": 1,
    "intervention_id": 1,
    "maintenance_type": "Preventive",
    "intervention": {
      "id": 1,
      "date": "2024-01-10T09:00:00.000000Z",
      "technician_name": "John Doe",
      "equipment": {
        "id": 1,
        "name": "Server X1",
        "type": "Server"
      }
    }
  }
}
```

## Migration Steps

1. **Run the new migration**:
   ```bash
   php artisan migrate
   ```

2. **If you have existing data**, you'll need to:
   - Create interventions for existing equipment-maintenance relationships
   - Update maintenance records to reference intervention IDs

## Testing the New API

### 1. First, create an intervention:
```bash
curl -X POST http://localhost:8080/api/interventions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "date": "2024-01-10T09:00:00Z",
    "technician_name": "John Doe",
    "note": "Regular maintenance intervention",
    "equipment_id": 1
  }'
```

### 2. Then create a maintenance linked to that intervention:
```bash
curl -X POST http://localhost:8080/api/maintenances \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "intervention_id": 1,
    "maintenance_type": "Preventive",
    "scheduled_date": "2024-01-15",
    "observations": "Regular preventive maintenance",
    "technician_id": 1
  }'
```

## Benefits of This Change

1. **Better Data Structure**: More logical relationship hierarchy
2. **Improved Traceability**: Maintenance is linked to specific interventions
3. **Enhanced Context**: Each maintenance record has full intervention context
4. **Better Reporting**: Can track maintenance per intervention and per equipment
5. **Scalability**: Supports multiple maintenance records per intervention

## API Version

- **Previous Version**: v1.0 (Equipment-based)
- **Current Version**: v2.0 (Intervention-based)
- **Documentation**: See `maintenance_api_documentation_v2.md`
