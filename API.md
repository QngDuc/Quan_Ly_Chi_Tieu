# API Documentation

## Authentication

### POST `/login_signup/api_register`
Register a new user account.

**Request:**
```json
{
  "full_name": "string",
  "email": "string",
  "password": "string",
  "confirm_password": "string"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Đăng ký thành công!",
  "redirect_url": "/dashboard"
}
```

### POST `/login_signup/api_login`
Login to existing account.

**Request:**
```json
{
  "email": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Đăng nhập thành công!",
  "redirect_url": "/dashboard"
}
```

## Transactions

### POST `/transactions/api_create`
Create a new transaction.

**Headers:**
- `Content-Type: application/json`
- `X-CSRF-TOKEN: {token}`

**Request:**
```json
{
  "category_id": 1,
  "amount": 100000,
  "description": "Mua sắm",
  "date": "2025-12-02",
  "csrf_token": "..."
}
```

### POST `/transactions/api_update`
Update existing transaction.

**Request:**
```json
{
  "id": 1,
  "category_id": 2,
  "amount": 150000,
  "description": "Updated description",
  "date": "2025-12-02",
  "csrf_token": "..."
}
```

### POST `/transactions/api_delete`
Delete a transaction.

**Request:**
```json
{
  "id": 1,
  "csrf_token": "..."
}
```

## Budgets (6 Jars)

### GET `/budgets/api_get_jars`
Get current month's jar allocations.

**Response:**
```json
{
  "success": true,
  "data": {
    "nec": { "percentage": 55, "amount": 11000000, "spent": 8500000 },
    "ffa": { "percentage": 10, "amount": 2000000, "saved": 1500000 },
    "edu": { "percentage": 10, "amount": 2000000, "spent": 500000 },
    "ltss": { "percentage": 10, "amount": 2000000, "saved": 2000000 },
    "play": { "percentage": 10, "amount": 2000000, "spent": 1200000 },
    "give": { "percentage": 5, "amount": 1000000, "spent": 300000 }
  },
  "total_income": 20000000
}
```

### POST `/budgets/api_update_percentages`
Update jar allocation percentages.

**Request:**
```json
{
  "nec": 55,
  "ffa": 10,
  "edu": 10,
  "ltss": 10,
  "play": 10,
  "give": 5,
  "csrf_token": "..."
}
```

### POST `/budgets/api_update_income`
Update total income for the month.

**Request:**
```json
{
  "income": 20000000,
  "csrf_token": "..."
}
```

## Goals

### POST `/goals/api_create`
Create a savings goal.

**Request:**
```json
{
  "name": "Mua laptop mới",
  "description": "MacBook Pro M3",
  "target_amount": 50000000,
  "deadline": "2026-06-30",
  "csrf_token": "..."
}
```

### POST `/goals/api_update`
Update a goal.

**Request:**
```json
{
  "id": 1,
  "name": "Updated name",
  "description": "Updated description",
  "target_amount": 60000000,
  "deadline": "2026-12-31",
  "csrf_token": "..."
}
```

### POST `/goals/api_delete`
Delete a goal.

**Request:**
```json
{
  "id": 1,
  "csrf_token": "..."
}
```

### POST `/goals/api_add_fund`
Add funds to a goal.

**Request:**
```json
{
  "goal_id": 1,
  "transaction_id": 5,
  "csrf_token": "..."
}
```

## Admin (Admin Role Required)

### POST `/admin/api_toggle_user_status`
Enable/disable a user account.

**Request:**
```json
{
  "user_id": 2,
  "is_active": 0
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật trạng thái thành công"
}
```

### POST `/admin/api_update_user_role`
Change user role (user ↔ admin).

**Request:**
```json
{
  "user_id": 2,
  "role": "admin"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cập nhật vai trò thành công"
}
```

**Restrictions:**
- Cannot modify role of user id=1 (primary admin)
- Cannot modify own role
- Cannot disable own account

## Profile

### POST `/profile/api_update`
Update user profile information.

**Request:**
```json
{
  "name": "Nguyễn Văn A",
  "email": "updated@example.com",
  "csrf_token": "..."
}
```

### POST `/profile/api_change_password`
Change user password.

**Request:**
```json
{
  "current_password": "old_password",
  "new_password": "new_password",
  "confirm_password": "new_password",
  "csrf_token": "..."
}
```

## Error Responses

All endpoints return standard error format:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation error)
- `401` - Unauthorized (not logged in)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found
- `500` - Internal Server Error

## CSRF Protection

All POST requests require a valid CSRF token. Include token in:
- Request body: `csrf_token`
- HTTP header: `X-CSRF-TOKEN`

Get token from meta tag:
```html
<meta name="csrf-token" content="...">
```
