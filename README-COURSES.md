# Szkolenia Online LIVE Feature

This document describes the implementation of the "Szkolenia Online LIVE" feature, which displays courses from the `admpnedu` database.

## Overview

The feature allows users to view a list of courses from the `admpnedu` database, displayed in a table format with columns for date, title, and trainer, sorted from newest to oldest. The page is accessible from the navigation menu via "Szkolenia otwarte->Szkolenia online LIVE".

## Implementation Details

The implementation includes:

1. **Database Configuration**:
   - A second database connection for "admpnedu" in `config/database.php`
   - Environment variables in `.env` for the second database connection

2. **Model**:
   - `Course` model that connects to the admpnedu database and the courses table
   - Formatted date accessor for better date display

3. **Controller**:
   - `CourseController` with an `onlineLive()` method that fetches courses ordered by date (newest first)
   - Error handling to gracefully handle database access issues

4. **View**:
   - Table display with columns for date, title, and trainer
   - Error handling to display a user-friendly message when there's a database access error

5. **Route**:
   - `/szkolenia-online-live` route that maps to the controller method

6. **Navigation**:
   - Updated navigation menu to link "Szkolenia otwarte->Szkolenia online LIVE" to the new page

## Database Access Issue

The feature is currently displaying an error message because the 'sail' user doesn't have access to the 'admpnedu' database. To fix this issue, you need to either:

1. Grant the 'sail' user access to the 'admpnedu' database using:
   ```sql
   GRANT ALL PRIVILEGES ON admpnedu.* TO 'sail'@'%';
   FLUSH PRIVILEGES;
   ```

2. Or update the database credentials in the `.env` file to use a user that already has access to the admpnedu database:
   ```
   DB_ADMPNEDU_USERNAME=your_user
   DB_ADMPNEDU_PASSWORD=your_password
   ```

## Database Structure

The `admpnedu` database contains several tables, including:

1. **courses**: The main table for course information
   - This table is expected to have columns like `title`, `date` (or `start_date`/`end_date`), and possibly `trainer` or `instructor_id`

2. **instructors**: Contains information about course instructors with columns:
   - `id`: Primary key
   - `title`: Instructor's title
   - `first_name`: Instructor's first name
   - `last_name`: Instructor's last name
   - `email`: Instructor's email
   - `phone`: Instructor's phone number
   - `bio`: Instructor's biography
   - `photo`: Path to instructor's photo
   - `signature`: Instructor's signature
   - `is_active`: Whether the instructor is active
   - `created_at`: Timestamp of when the record was created
   - `updated_at`: Timestamp of when the record was last updated

The `Course` model has been designed to be flexible and handle different possible structures of the `courses` table:

- It can use `date`, `start_date`, or `created_at` for the date display
- It can use `trainer` directly or potentially reference an instructor via `instructor_id`

If the actual structure of the `courses` table differs significantly from what's expected, you may need to further update the `Course` model and the view.