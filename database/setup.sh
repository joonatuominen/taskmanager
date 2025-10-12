#!/bin/bash

# Task Manager Database Setup Script
# This script creates the MySQL database and sets up the schema

echo "==================================="
echo "Task Manager Database Setup"
echo "==================================="

# Database configuration
DB_NAME="taskmanager"
DB_USER="phpmyadmin"  # Updated to match config.php
DB_PASS="bzQx@N4z7q!oqsaVtQ*R"      # Updated to match config.php

echo "Setting up database: $DB_NAME"

# Check if MySQL is running
if ! command -v mysql &> /dev/null; then
    echo "Error: MySQL is not installed or not in PATH"
    exit 1
fi

# Test MySQL connection
if ! mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "SELECT 1;" &> /dev/null; then
    echo "Error: Cannot connect to MySQL with provided credentials"
    echo "Please check your MySQL username and password in this script"
    exit 1
fi

echo "✓ MySQL connection successful"

# Create database if it doesn't exist
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo "✓ Database '$DB_NAME' created/verified"
else
    echo "✗ Failed to create database"
    exit 1
fi

# Execute schema file
echo "Setting up database schema..."
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" < "$(dirname "$0")/schema.sql"

if [ $? -eq 0 ]; then
    echo "✓ Database schema created successfully"
else
    echo "✗ Failed to create database schema"
    exit 1
fi

# Execute procedures file
echo "Setting up stored procedures and functions..."
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" < "$(dirname "$0")/procedures.sql"

if [ $? -eq 0 ]; then
    echo "✓ Stored procedures and functions created successfully"
else
    echo "✗ Failed to create stored procedures and functions"
    exit 1
fi

# Verify setup by checking if tables exist
TABLES_COUNT=$(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" -e "SHOW TABLES;" | wc -l)

if [ "$TABLES_COUNT" -gt 1 ]; then
    echo "✓ Database setup completed successfully!"
    echo ""
    echo "Tables created:"
    mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME" -e "SHOW TABLES;"
    echo ""
    echo "Sample data has been inserted. You can now:"
    echo "1. Update the database credentials in database/config.php"
    echo "2. Start developing your PHP + React task management application"
    echo ""
    echo "Useful queries to get started:"
    echo "- SELECT * FROM task_dashboard;        -- View all tasks with calculated urgency"
    echo "- SELECT * FROM todays_tasks;          -- View today's tasks"
    echo "- SELECT * FROM upcoming_tasks;        -- View upcoming tasks (next 7 days)"
    echo "- SELECT * FROM overdue_tasks;         -- View overdue tasks"
    echo "- SELECT * FROM task_statistics;       -- View task statistics"
else
    echo "✗ Database setup may have failed - no tables found"
    exit 1
fi