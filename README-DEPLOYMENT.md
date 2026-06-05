# Deployment Guide - Blog Management System

This guide walks you through deploying the Blog Management System to Render.

## Prerequisites

- GitHub account
- Render account (free tier available at https://render.com)
- Git installed locally

## Step 1: Push to GitHub

### 1.1 Create a GitHub Repository

1. Go to https://github.com/new
2. Create a new repository:
   - Name: `blog-management-system` (or your preferred name)
   - Description: "Blog Management System for JobYaari Assessment"
   - Visibility: Public or Private
   - **Do NOT** initialize with README, .gitignore, or license (we already have these)

### 1.2 Initialize and Push Local Repository

Run these commands in your project directory:

```bash
# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: Blog Management System"

# Add GitHub remote (replace YOUR_USERNAME and YOUR_REPO_NAME)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git

# Push to GitHub
git branch -M main
git push -u origin main
```

## Step 2: Deploy to Render

### 2.1 Connect GitHub to Render

1. Go to https://dashboard.render.com/
2. Click "New +" → "Web Service"
3. Connect your GitHub account if not already connected
4. Select your `blog-management-system` repository

### 2.2 Configure Web Service

Fill in the following settings:

- **Name**: `blog-management-system` (or your preferred name)
- **Region**: Choose closest to your target audience
- **Branch**: `main`
- **Runtime**: `PHP`
- **Build Command**: 
  ```bash
  composer install --no-dev --optimize-autoloader && php schema/migrate_sqlite.php
  ```
- **Start Command**:
  ```bash
  php -S 0.0.0.0:$PORT -t public/
  ```

### 2.3 Add Environment Variables

In the "Environment Variables" section, add:

- `DB_CONNECTION` = `sqlite`
- `DB_NAME` = `blog_local`

### 2.4 Add Persistent Disk (Important!)

1. Scroll down to "Disk"
2. Click "Add Disk"
3. Configure:
   - **Name**: `blog-data`
   - **Mount Path**: `/opt/render/project/src`
   - **Size**: 1 GB (free tier)

This ensures your SQLite database persists across deployments.

### 2.5 Deploy

1. Click "Create Web Service"
2. Render will automatically:
   - Clone your repository
   - Install Composer dependencies
   - Run the SQLite migration script
   - Start the PHP development server

## Step 3: Access Your Deployed Application

Once deployment completes (usually 2-5 minutes):

1. Render will provide a URL like: `https://blog-management-system.onrender.com`
2. Access your blog at: `https://your-app.onrender.com/blogs`
3. Access admin panel at: `https://your-app.onrender.com/admin/login`

### Default Admin Credentials

- **Username**: `admin`
- **Password**: `Password@123`

**⚠️ IMPORTANT**: Change this password immediately after first login!

## Step 4: Post-Deployment Configuration

### 4.1 Update Admin Password

1. Log into admin panel
2. (Optional) Create a password change feature or update directly in database

### 4.2 Set Up Custom Domain (Optional)

1. In Render dashboard, go to your web service
2. Click "Settings" → "Custom Domain"
3. Add your domain and follow DNS configuration instructions

### 4.3 Enable HTTPS

Render automatically provisions SSL certificates for:
- Your `.onrender.com` subdomain
- Custom domains (via Let's Encrypt)

No additional configuration needed!

## Troubleshooting

### Build Fails

**Issue**: Composer install fails

**Solution**: 
- Ensure `composer.json` and `composer.lock` are committed to git
- Check Render build logs for specific errors

### Database Not Persisting

**Issue**: Blog posts disappear after redeployment

**Solution**:
- Verify persistent disk is attached
- Check mount path is `/opt/render/project/src`
- Ensure SQLite database is created in project root

### App Not Loading

**Issue**: Service is running but site shows errors

**Solution**:
1. Check Render logs: Dashboard → Your Service → "Logs"
2. Verify environment variables are set correctly
3. Ensure PHP version compatibility (requires PHP >= 7.4)

### File Uploads Not Working

**Issue**: Image uploads fail in admin panel

**Solution**:
- File uploads are stored in `public/uploads/`
- They will persist with the disk mount
- Check directory permissions in logs

## Environment Variables Reference

| Variable | Production Value | Description |
|----------|-----------------|-------------|
| `DB_CONNECTION` | `sqlite` | Database driver |
| `DB_NAME` | `blog_local` | Database name |
| `PHP_VERSION` | `8.1` | PHP runtime version (optional) |

## Updating Your Deployment

When you make changes:

```bash
# Make your changes
git add .
git commit -m "Description of changes"
git push origin main
```

Render will automatically:
1. Detect the push
2. Rebuild your application
3. Deploy the new version
4. Keep your database intact (thanks to persistent disk)

## Free Tier Limitations

Render's free tier includes:
- 750 hours/month of runtime
- Services spin down after 15 minutes of inactivity
- ~30 second cold start when accessing after spin down
- 1 GB disk storage

For production use, consider upgrading to a paid tier.

## Security Checklist for Production

- [ ] Change default admin password
- [ ] Review and restrict admin access
- [ ] Enable additional security headers
- [ ] Set up regular database backups
- [ ] Monitor error logs regularly
- [ ] Keep dependencies updated (`composer update`)
- [ ] Set up monitoring/alerts for downtime

## Support

For issues specific to:
- **Application**: Check application logs and README.md
- **Render Platform**: Visit https://render.com/docs or support.render.com

## Additional Resources

- [Render PHP Documentation](https://render.com/docs/deploy-php)
- [SQLite on Render](https://render.com/docs/disks)
- [Custom Domains on Render](https://render.com/docs/custom-domains)
