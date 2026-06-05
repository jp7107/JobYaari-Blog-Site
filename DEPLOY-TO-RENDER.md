# 🚀 Quick Deploy to Render - Step by Step

Your code is now on GitHub! Follow these steps to deploy to Render.

## ✅ GitHub Repository
**URL**: https://github.com/jp7107/JobYaari-Blog-Site.git

---

## 📋 Render Deployment Steps

### Step 1: Go to Render Dashboard
1. Open: https://dashboard.render.com/
2. Sign in (or create free account if you don't have one)
3. Click the **"New +"** button (top right)
4. Select **"Web Service"**

### Step 2: Connect Your Repository
1. Click **"Connect account"** if GitHub isn't connected
2. Search for: `JobYaari-Blog-Site`
3. Click **"Connect"** next to your repository

### Step 3: Configure the Web Service

Fill in these settings exactly as shown:

#### Basic Settings
- **Name**: `jobyaari-blog-site` (or any name you prefer)
- **Region**: Choose closest to you (e.g., `Singapore` or `Oregon USA`)
- **Branch**: `main`
- **Root Directory**: (leave blank)
- **Runtime**: `PHP`

#### Build & Start Commands
- **Build Command**:
  ```bash
  composer install --no-dev --optimize-autoloader && php schema/migrate_sqlite.php
  ```

- **Start Command**:
  ```bash
  php -S 0.0.0.0:$PORT -t public/
  ```

#### Instance Type
- Select: **Free** (or paid if you prefer)

### Step 4: Add Environment Variables

Scroll down to **"Environment Variables"** and add these:

| Key | Value |
|-----|-------|
| `DB_CONNECTION` | `sqlite` |
| `DB_NAME` | `blog_local` |

Click **"Add Environment Variable"** for each one.

### Step 5: Add Persistent Disk (CRITICAL!)

Scroll down to **"Disks"** section:

1. Click **"Add Disk"**
2. Configure:
   - **Name**: `blog-data`
   - **Mount Path**: `/opt/render/project/src`
   - **Size**: `1` GB

⚠️ **This is critical** - without this, your database will be deleted on every deployment!

### Step 6: Deploy!

1. Click **"Create Web Service"** at the bottom
2. Render will start deploying (takes 2-5 minutes)
3. Watch the logs as it builds

### Step 7: Access Your Live Site

Once deployment completes:

1. Render gives you a URL like: `https://jobyaari-blog-site.onrender.com`
2. Access your blog: `https://your-app.onrender.com/blogs`
3. Admin panel: `https://your-app.onrender.com/admin/login`

#### 🔐 Default Login Credentials
- **Username**: `admin`
- **Password**: `Password@123`

⚠️ **Change this password immediately after first login!**

---

## 📝 What Happens During Deployment

1. ✅ Render clones your GitHub repository
2. ✅ Installs Composer dependencies (`composer install`)
3. ✅ Creates SQLite database (`migrate_sqlite.php`)
4. ✅ Seeds 5 sample blog posts
5. ✅ Creates default admin user
6. ✅ Starts PHP server on port provided by Render

---

## 🔧 Troubleshooting

### ❌ Build Fails
- Check the "Logs" tab in Render dashboard
- Most common issue: Composer dependencies not installing
- Solution: Ensure `composer.json` exists in repo (✅ it does)

### ❌ Database Not Persisting
- Did you add the **Disk** in Step 5?
- Verify mount path is exactly: `/opt/render/project/src`

### ❌ Site Shows Errors
- Check Render logs for PHP errors
- Verify environment variables are set correctly
- Ensure SQLite database was created (check build logs)

---

## 🔄 Update Your Live Site

When you make changes:

```bash
# In your project directory
git add .
git commit -m "Your change description"
git push origin main
```

Render will **automatically detect the push** and redeploy! 🎉

---

## ⚡ Free Tier Limitations

Render's free tier:
- ✅ 750 hours/month runtime
- ⚠️ Spins down after 15 min inactivity
- ⚠️ ~30 sec cold start after spin down
- ✅ 1 GB persistent storage
- ✅ Auto SSL certificates

For production, consider upgrading to paid tier ($7/month).

---

## 📚 Additional Resources

- **Render PHP Docs**: https://render.com/docs/deploy-php
- **Your GitHub Repo**: https://github.com/jp7107/JobYaari-Blog-Site
- **Render Dashboard**: https://dashboard.render.com/

---

## ✅ Deployment Checklist

Use this checklist while deploying:

- [ ] Logged into Render dashboard
- [ ] Connected GitHub repository
- [ ] Set Runtime to "PHP"
- [ ] Added Build Command
- [ ] Added Start Command
- [ ] Added DB_CONNECTION environment variable
- [ ] Added DB_NAME environment variable
- [ ] Added Persistent Disk (1 GB)
- [ ] Set Mount Path to `/opt/render/project/src`
- [ ] Clicked "Create Web Service"
- [ ] Waited for deployment to complete
- [ ] Visited live URL
- [ ] Tested admin login
- [ ] Changed default admin password

---

**Need help?** Check the logs in Render dashboard or refer to README-DEPLOYMENT.md for detailed troubleshooting.
