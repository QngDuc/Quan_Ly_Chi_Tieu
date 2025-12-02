# Changelog

All notable changes to SmartSpending will be documented in this file.

## [4.0.0] - 2025-12-02

### Added
- Admin/User role system with permissions
- Admin panel for user management
- User status control (active/inactive)
- Role management (promote/demote users)
- AdminMiddleware for protected routes
- Environment configuration support (.env)
- MIT License file
- Contributing guidelines

### Changed
- Database schema updated to v4.0.0
- User table now includes `role` and `is_active` columns
- Authentication checks now validate active status
- First registered user automatically becomes admin
- Consolidated database files (removed migrations folder)

### Security
- Enhanced authentication with role-based access control
- Admin-only routes protection
- Prevention of self-role modification
- Protection of primary admin account (id=1)

## [3.0.0] - 2025-12-01

### Added
- 6 Jars Method budget system (T. Harv Eker)
- Jar allocations tracking
- Custom MVC framework
- PSR-4 autoloading with Composer
- CSRF Protection middleware
- Service layer (FinancialUtils, Validator)
- Stored procedures and triggers
- Database views for reporting

### Changed
- Restructured views (flat structure)
- Reorganized assets by page
- Consolidated middleware files
- Simplified .gitignore

### Fixed
- Database column name consistency (transaction_date → date)
- Transaction type auto-assignment via trigger

## [2.0.0] - 2025-11-XX

### Added
- Goals tracking system
- Reports with charts (Chart.js)
- Category management
- Transaction filtering
- Dashboard with 3-month trends

## [1.0.0] - 2025-10-XX

### Added
- Initial release
- Basic transaction management
- User authentication
- Dashboard overview
