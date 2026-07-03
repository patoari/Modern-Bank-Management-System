# 🚀 Quick Start Guide - Bank Management System

## 📋 Prerequisites
- PHP 8.3+
- MySQL 8.0+
- Node.js 18+
- Composer
- Git

## 🔧 Installation & Setup

### 1. Backend Setup

```bash
# Navigate to backend directory
cd backend

# Install dependencies
composer install

# Create .env file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations (creates all tables including new feature tables)
php artisan migrate

# Seed initial data (optional)
php artisan db:seed

# Start development server
php artisan serve  # Runs on http://localhost:8000
```

### 2. Frontend Setup

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev  # Runs on http://localhost:5173
```

## 🧪 Testing New Features

### Test Password Reset
```bash
# 1. Navigate to http://localhost:5173/auth/forgot-password
# 2. Enter your email
# 3. Check console or mail service for reset link
# 4. Click link or enter token
# 5. Set new password
```

### Test Beneficiary Management
```bash
# 1. Login to dashboard
# 2. Go to "Beneficiaries" menu
# 3. Click "+ Add Beneficiary"
# 4. Fill form with account details
# 5. Verify account exists
# 6. Save and manage beneficiaries
```

### Test Standing Instructions
```bash
# 1. Go to "Standing Instructions"
# 2. Click "+ New Instruction"
# 3. Select from account
# 4. Choose recipient (to account or beneficiary)
# 5. Set amount, frequency, date range
# 6. Save and monitor execution
```

### Test Notifications
```bash
# 1. Go to "Notifications" menu
# 2. Should display all notifications
# 3. Mark individual or all as read
# 4. Delete notifications
# 5. Filter by Unread/Read status
```

### Test Cheque Management
```bash
# 1. Go to "Cheque Management"
# 2. Click "+ Request Cheque Book"
# 3. Select account and quantity
# 4. Choose delivery mode
# 5. View cheque book requests and cheques
# 6. Stop individual cheques if needed
```

### Test Customer Complaints
```bash
# 1. Go to "Complaints"
# 2. Click "+ Register Complaint"
# 3. Fill category, type, priority, description
# 4. Submit and track reference
# 5. View statistics
# 6. Add remarks to track resolution
```

### Test ATM Locator
```bash
# API Test with curl:
curl -X POST http://localhost:8000/api/v1/atms/nearby \
  -H "Content-Type: application/json" \
  -d '{"latitude": 40.7128, "longitude": -74.0060, "radius": 5}'

# Or use frontend to display nearby ATMs
```

## 🔐 Security Testing

### Test 2FA Setup
```bash
# Login and enable 2FA:
curl -X POST http://localhost:8000/api/v1/auth/2fa/enable \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"channel": "email"}'
```

### Test Account Lockout
```bash
# Make 5+ failed login attempts with wrong password
# Account should lock for 30 minutes
# Reset after expiry or admin unlock
```

## 📊 Database Verification

```bash
# Connect to database
mysql -u root -p bank_management

# Verify new tables exist
SHOW TABLES;

# Check specific table structure
DESC beneficiaries;
DESC standing_instructions;
DESC customer_complaints;
DESC cheques;
DESC otp_tokens;
DESC password_reset_tokens;

# View sample data
SELECT * FROM beneficiaries;
SELECT * FROM standing_instructions;
SELECT * FROM customer_complaints;
```

## 🐛 Troubleshooting

### Issue: Migration fails
```bash
# Check database connection in .env
# Ensure database exists
# Run: php artisan migrate:refresh (clears all data!)
```

### Issue: Frontend can't reach API
```bash
# Check CORS settings in backend/config/cors.php
# Verify API URL in frontend axios config
# Ensure backend is running on correct port
```

### Issue: Email not sending for password reset
```bash
# Update MAIL_* variables in .env
# Test with: php artisan tinker
# Then: Mail::raw('Test', function ($m) { $m->to('test@example.com'); });
```

### Issue: 2FA OTP not appearing
```bash
# Check otp_tokens table for recent entries
# Verify email is configured
# Check mail logs in storage/logs/
```

## 📈 Performance Monitoring

### Check Active Database Connections
```sql
SHOW PROCESSLIST;
```

### Monitor API Response Time
```bash
# Add to .env
APP_DEBUG=true
LOG_CHANNEL=stack

# Check logs in storage/logs/
tail -f storage/logs/laravel.log
```

### Frontend Performance
```bash
# Open Chrome DevTools -> Network tab
# Monitor bundle size and load times
# Profile with Lighthouse
```

## 📚 API Documentation

### Full OpenAPI/Swagger Setup (Optional)
```bash
# Install Swagger package
composer require "darkaonline/swagger-lume:v8.*"

# Generate docs
php artisan l5-swagger:generate

# View at: http://localhost:8000/api/documentation
```

## 🔄 Deployment Preparation

### Before Production Deployment:

1. **Security**
   ```bash
   # Generate APP_KEY
   php artisan key:generate
   
   # Set production environment
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Database**
   ```bash
   # Run migrations on production database
   php artisan migrate --force
   
   # Backup database regularly
   ```

3. **Frontend Build**
   ```bash
   npm run build
   # Generates dist/ folder for deployment
   ```

4. **Environment Variables**
   ```bash
   # Set in production .env
   DB_PASSWORD=secure_password
   MAIL_PASSWORD=secure_password
   APP_KEY=base64:xxxxx
   ```

## 📞 Support Commands

### Helpful Artisan Commands
```bash
# Clear cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Generate app key
php artisan key:generate

# Create admin user
php artisan tinker
> \App\Models\User::factory()->create(['user_type' => 'admin']);

# List all routes
php artisan route:list

# Reset permissions
php artisan permission:cache-reset
```

### Database Helpers
```bash
# Tinker shell for quick testing
php artisan tinker

# Inside tinker:
User::all();
Beneficiary::where('customer_id', 1)->get();
StandingInstruction::where('status', 'active')->get();
```

## 🎯 Verification Checklist

After setup, verify:
- [ ] Backend running on http://localhost:8000
- [ ] Frontend running on http://localhost:5173
- [ ] Database migrations completed
- [ ] Login works with default credentials
- [ ] Password reset email sends
- [ ] Beneficiaries can be added/edited/deleted
- [ ] Standing instructions can be created
- [ ] Notifications display correctly
- [ ] Cheque management works
- [ ] Complaints can be registered
- [ ] ATM locator returns results
- [ ] All forms validate properly
- [ ] Error messages display clearly

## 🎓 Learning Resources

- Laravel Documentation: https://laravel.com/docs
- Vue 3 Documentation: https://vuejs.org
- Tailwind CSS: https://tailwindcss.com
- Axios HTTP Client: https://axios-http.com
- Pinia State Management: https://pinia.vuejs.org

---

**Ready to go live?** Ensure all checklist items are completed and run full test suite before production deployment!
