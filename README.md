# Blog Management System

A full-stack PHP/MySQL web application for the JobYaari developer assessment, providing a public-facing blog interface and a secure administrative panel for content management.

## Features

- **Public Blog Interface**: Browse, search, and filter blog posts with responsive design
- **AJAX-Based Filtering**: Real-time filtering by category, date, and search without page reloads
- **Admin Panel**: Secure authentication with CRUD operations for blog management
- **Image Upload**: Support for blog post images with validation
- **Rate Limiting**: Failed login attempt tracking and account lockout
- **Responsive Design**: Mobile and desktop optimized layouts

## Requirements

### System Requirements
- PHP >= 7.4
- MySQL >= 5.7 or MariaDB >= 10.2
- Web server (Apache/Nginx)
- Composer

### Required PHP Extensions
- **mysqli**: For MySQL database connectivity
- **gd**: For image processing
- **fileinfo**: For file type validation
- **mbstring**: For string handling
- **json**: For AJAX responses

### Verify PHP Extensions
```bash
php -m | grep -E 'mysqli|gd|fileinfo|mbstring|json'
```

If any extensions are missing, install them:
```bash
# Ubuntu/Debian
sudo apt-get install php-mysqli php-gd php-mbstring

# macOS (using Homebrew)
brew install php
```

## Installation

### 1. Clone or Download the Repository

```bash
cd /path/to/your/webroot
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

Copy the example environment file and configure your settings:

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=blog_system
DB_USER=root
DB_PASSWORD=your_password
```

### 4. Database Setup

#### Create Database and Tables

Run the SQL schema file to create the database and tables:

```bash
mysql -u root -p < schema/database.sql
```

Or manually:

```bash
mysql -u root -p
```

Then execute the contents of `schema/database.sql`.

The schema includes:
- `blog_posts` table: Stores blog post data with indexes on `created_date`, `category`, and full-text search on `title` and `content`
- `admin_users` table: Stores admin credentials with secure password hashing
- `login_attempts` table: Tracks failed login attempts for rate limiting

#### Default Admin User

A default admin user is created during database setup:
- **Username**: `admin`
- **Password**: `Password@123`

**Important**: Change this password after first login in production.

### 5. File Permissions

Set appropriate permissions for the uploads directory:

```bash
chmod 755 public/uploads
```

Uploaded files will automatically receive 644 permissions (rw-r--r--).

### 6. Web Server Configuration

#### Apache

The project includes a `.htaccess` file in the `public/` directory. Ensure:
- `mod_rewrite` is enabled
- `AllowOverride All` is set for the directory

#### Nginx

Add this to your server block:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/project/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 7. Set Document Root

Configure your web server to use the `public/` directory as the document root:

```
/path/to/project/public
```

### 8. Verify Installation

Visit your application in a browser:
- **Public Blog**: `http://your-domain.com/`
- **Admin Panel**: `http://your-domain.com/admin/login`

## Project Structure

```
├── config/
│   └── database.php          # Database configuration
├── public/
│   ├── assets/
│   │   ├── css/              # Stylesheets
│   │   └── js/               # JavaScript files
│   ├── uploads/              # Uploaded blog images
│   ├── .htaccess             # Apache rewrite rules
│   └── index.php             # Application entry point
├── schema/
│   └── database.sql          # Database schema
├── src/
│   ├── Controllers/          # Request handlers
│   ├── Models/               # Data models
│   ├── Services/             # Business logic
│   ├── Middleware/           # Authentication middleware
│   ├── Database.php          # Database connection
│   └── Router.php            # Request routing
├── templates/
│   ├── admin/                # Admin panel templates
│   ├── blog/                 # Public blog templates
│   ├── error.php             # Error page
│   └── layout.php            # Main layout
├── tests/                    # PHPUnit and property-based tests
├── vendor/                   # Composer dependencies
├── .env                      # Environment configuration
├── composer.json             # Dependency management
└── phpunit.xml               # Test configuration
```

## Testing

The project uses PHPUnit for unit and integration tests, and Eris for property-based testing.

### Run All Tests

```bash
./vendor/bin/phpunit
```

### Run Specific Test Suites

```bash
# Unit tests only
./vendor/bin/phpunit --testsuite unit

# Property-based tests only
./vendor/bin/phpunit --testsuite property

# Integration tests only
./vendor/bin/phpunit --testsuite integration
```

### Run Tests with Coverage

```bash
./vendor/bin/phpunit --coverage-html coverage
```

## Usage

### Public Interface

#### Browse Blog Posts
- Visit the homepage to see all blog posts
- Posts are displayed in reverse chronological order

#### Search
- Use the search box to find posts by keyword
- Search looks in both title and content fields

#### Filter by Category
- Select a category from the dropdown to filter posts
- Choose "All Categories" to reset

#### Filter by Date
- Select a date range: Today, This Week, This Month, or Custom Range
- Custom ranges allow specific start and end dates

#### View Blog Details
- Click on any blog post to view the full content

### Admin Panel

#### Login
1. Navigate to `/admin/login`
2. Enter username and password
3. Default credentials: `admin` / `Password@123`

#### Create Blog Post
1. Click "Create New Blog" button
2. Fill in required fields:
   - Title (max 200 characters)
   - Content (max 50,000 characters)
   - Short Description (max 500 characters)
   - Category (optional, max 100 characters)
3. Upload an image (optional):
   - Formats: JPG, JPEG, PNG, GIF
   - Max size: 5MB
4. Click "Submit"

#### Edit Blog Post
1. Click "Edit" button next to any blog post
2. Modify fields as needed
3. Upload a new image to replace the existing one (optional)
4. Click "Update"

#### Delete Blog Post
1. Click "Delete" button next to any blog post
2. Confirm deletion in the dialog
3. The blog post and associated image will be permanently removed

#### Logout
- Click the "Logout" button to end your session

## Security Features

### Password Security
- Passwords stored using bcrypt hashing with automatic salt
- Secure password comparison prevents timing attacks

### Rate Limiting
- Maximum 5 failed login attempts per username within 15 minutes
- Account locked for 15 minutes after threshold exceeded
- Login attempts tracked with timestamp and IP address

### SQL Injection Prevention
- All queries use prepared statements with parameter binding
- Special characters in search queries are properly escaped

### File Upload Security
- File type validation using fileinfo extension
- File size restrictions (5MB maximum)
- Unique filename generation prevents overwrites
- Uploaded files stored outside document root access when possible

### Session Security
- 30-minute inactivity timeout
- Session regeneration on authentication
- Secure session configuration

## Error Logging

Errors and exceptions are logged to PHP's error log with:
- Timestamp
- Error message
- Stack trace
- Request context

Configure error logging in your `php.ini`:

```ini
error_reporting = E_ALL
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
```

## Troubleshooting

### Database Connection Fails

**Error**: "Service temporarily unavailable"

**Solutions**:
1. Verify database credentials in `.env`
2. Check MySQL service is running: `sudo service mysql status`
3. Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`
4. Check error logs for detailed connection errors

### File Upload Fails

**Error**: "Insufficient permissions"

**Solutions**:
1. Verify uploads directory permissions: `ls -la public/uploads`
2. Set correct permissions: `chmod 755 public/uploads`
3. Check PHP `upload_max_filesize` and `post_max_size` settings
4. Ensure disk space is available

### Missing PHP Extension

**Error**: "Required extension [name] is missing"

**Solutions**:
1. Install the missing extension (see Requirements section)
2. Restart web server: `sudo service apache2 restart` or `sudo service nginx restart`
3. Verify extension is loaded: `php -m | grep [extension_name]`

### Sessions Not Working

**Error**: "Please log in again"

**Solutions**:
1. Check PHP session directory is writable
2. Verify `session.save_path` in `php.ini`
3. Clear browser cookies
4. Check server time is accurate (affects session timeout)

### AJAX Filters Not Working

**Error**: Filters don't update results

**Solutions**:
1. Check browser console for JavaScript errors
2. Verify jQuery is loaded
3. Check network tab for failed AJAX requests
4. Ensure server returns valid JSON responses

## Performance Optimization

### Database Indexes
The schema includes optimized indexes:
- `idx_created_date`: Fast sorting by date
- `idx_category`: Quick category filtering
- `idx_search`: Full-text search on title and content

### Caching Recommendations
Consider implementing:
- OpCode caching (OPcache)
- Query result caching
- Static file caching with CDN

## Deployment Checklist

Before deploying to production:

- [ ] Change default admin password
- [ ] Update `.env` with production database credentials
- [ ] Set `display_errors = Off` in `php.ini`
- [ ] Enable `log_errors = On` in `php.ini`
- [ ] Configure error log path
- [ ] Set appropriate file permissions (755 for directories, 644 for files)
- [ ] Enable HTTPS/SSL
- [ ] Configure database backups
- [ ] Set up monitoring and alerting
- [ ] Review and adjust `session.cookie_secure` and `session.cookie_httponly`
- [ ] Configure firewall rules
- [ ] Enable PHP OPcache
- [ ] Set up automated security updates

## License

MIT License - See project repository for details.

## Support

For issues or questions, contact the development team or refer to the project documentation.
