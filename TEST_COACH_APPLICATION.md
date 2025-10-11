# 🧪 Test Guide - Coach Application

**Feature:** Apply as a Coach  
**Testing:** SQL fix for `requester_user_id` column  
**Date:** 11 octobre 2025

---

## 🎯 What We're Testing

After fixing the SQL error where queries used `user_id` instead of `requester_user_id`, we need to verify:

1. ✅ Coach application form loads without errors
2. ✅ Form submission works (no SQL errors)
3. ✅ Duplicate submission prevention works
4. ✅ Admin can view submitted requests
5. ✅ Email notifications are sent

---

## 📋 Test Scenarios

### Scenario 1: New Coach Application (First Time)

**Steps:**

1. **Logout** from WordPress (if logged in as admin)
   - Go to: `Dashboard → Logout`

2. **Create a test member account** (if you don't have one)
   - Go to: `http://localhost/wordpress/member-registration`
   - Fill in the registration form
   - Submit and wait for auto-redirect

3. **Navigate to Coach Application**
   - Go to: `http://localhost/wordpress/coach-role-request`
   - OR from Member Portal, click "Apply to Become a Coach"

4. **Fill out the Coach Application Form**
   ```
   First Name: [Your name]
   Last Name: [Your last name]
   Email: [Your email]
   Phone: +254712345678
   Years of Experience: 5
   Specialization: Junior Golf Training
   Certifications: PGA Level 2, First Aid Certified
   Experience: I have been coaching junior golfers for 5 years...
   Reference Name: John Doe
   Reference Contact: +254723456789
   ```

5. **Submit the form**

**Expected Results:**
- ✅ **No SQL error** appears
- ✅ **Success message** displays: "Your coach application has been submitted!"
- ✅ **Email sent** to admin about new request
- ✅ **Redirected** or shown confirmation

**If you see this error, the fix didn't work:**
```
❌ WordPress database error: [Unknown column 'user_id' in 'where clause']
```

---

### Scenario 2: Duplicate Application Prevention

**Steps:**

1. **Stay logged in** as the same member
2. **Go back to Coach Application page**
   - `http://localhost/wordpress/coach-role-request`

**Expected Results:**
- ✅ **Warning message** appears: "You have a pending coach role request"
- ✅ **Shows submission date** and status
- ✅ **Form is disabled** or hidden
- ✅ **No duplicate entry** in database

---

### Scenario 3: Admin View Requests

**Steps:**

1. **Logout** from member account
2. **Login as Administrator**
   - Go to: `http://localhost/wordpress/wp-admin`
   - Use your admin credentials

3. **Navigate to Role Requests**
   - Dashboard → Junior Golf Kenya → Role Requests
   - OR: `http://localhost/wordpress/wp-admin/admin.php?page=juniorgolfkenya-role-requests`

4. **View the submitted request**

**Expected Results:**
- ✅ **Request appears** in the list
- ✅ **Member name displayed** (from JOIN with users table)
- ✅ **All details visible**: name, email, phone, experience, etc.
- ✅ **Status shows**: "Pending"
- ✅ **Actions available**: Approve / Reject buttons

---

### Scenario 4: Approve Coach Request

**Steps:**

1. **From Role Requests page** (as admin)
2. **Click "Approve"** on the test request
3. **Confirm approval**

**Expected Results:**
- ✅ **Request status** changes to "Approved"
- ✅ **User role updated** to `jgk_coach`
- ✅ **Email sent** to applicant about approval
- ✅ **Applicant can access** Coach Dashboard

---

### Scenario 5: Coach Dashboard Access

**Steps:**

1. **Logout** from admin
2. **Login as the approved coach** (member account)
3. **Go to Coach Dashboard**
   - `http://localhost/wordpress/coach-dashboard`
   - OR from Member Portal → "Coach Dashboard" link

**Expected Results:**
- ✅ **Dashboard loads** successfully
- ✅ **Coach features** are accessible
- ✅ **Can view assigned members**
- ✅ **No "insufficient permissions" error**

---

## 🔍 Database Verification

### Check Database Directly

**Open phpMyAdmin:**
- Go to: `http://localhost/phpmyadmin`

**Run this query:**
```sql
SELECT * FROM wp_jgf_role_requests ORDER BY created_at DESC LIMIT 5;
```

**Check the data:**
- ✅ Column `requester_user_id` contains user ID (not NULL)
- ✅ Column `requested_role` = 'jgk_coach'
- ✅ Column `status` = 'pending' (before approval)
- ✅ All form fields saved correctly

**Sample expected row:**
```
id: 1
requester_user_id: 5          ← Should have user ID here
requested_role: jgk_coach
first_name: John
last_name: Doe
email: john@example.com
phone: +254712345678
years_experience: 5
specialization: Junior Golf Training
certifications: PGA Level 2...
experience: I have been coaching...
status: pending
created_at: 2025-10-11 14:30:00
```

---

## 🐛 Troubleshooting

### Error: "Unknown column 'user_id'"

**Problem:** SQL fix not applied correctly

**Solution:**
1. Check files were saved:
   - `includes/class-juniorgolfkenya-activator.php`
   - `juniorgolfkenya.php`
2. Clear WordPress cache
3. Deactivate and reactivate plugin
4. Try again

---

### Error: "You must be logged in"

**Problem:** Not logged in or session expired

**Solution:**
1. Login to WordPress first
2. Make sure you're logged in as a member (not admin)
3. Try again

---

### Error: Form doesn't submit (Ajax error)

**Problem:** JavaScript or Ajax issue

**Solution:**
1. Open browser console (F12)
2. Look for JavaScript errors
3. Check Network tab for failed requests
4. Check if jQuery is loaded

---

### Warning: No coaches available

**Problem:** No coach users in system yet

**Solution:**
This is normal for first application! After approval:
1. User becomes a coach
2. They will appear in coach lists
3. Can be assigned to members

---

## ✅ Success Checklist

After testing, verify all these items:

- [ ] Coach application form loads without SQL errors
- [ ] Form submission works successfully
- [ ] Success message appears after submission
- [ ] Duplicate submission is prevented
- [ ] Request appears in admin Role Requests page
- [ ] Member name shows correctly (JOIN works)
- [ ] Admin can approve request
- [ ] User role changes to `jgk_coach` after approval
- [ ] Approved coach can access Coach Dashboard
- [ ] Database has correct data in `requester_user_id` column
- [ ] No SQL errors in WordPress debug log

---

## 📊 Test Results Template

```
TEST DATE: ___________
TESTER: ___________

SCENARIO 1 - New Application:
[ ] Form loads: PASS / FAIL
[ ] Submit works: PASS / FAIL
[ ] No SQL error: PASS / FAIL
[ ] Success message: PASS / FAIL
Notes: _______________________

SCENARIO 2 - Duplicate Prevention:
[ ] Warning shows: PASS / FAIL
[ ] Form disabled: PASS / FAIL
Notes: _______________________

SCENARIO 3 - Admin View:
[ ] Request visible: PASS / FAIL
[ ] Data correct: PASS / FAIL
[ ] JOIN works: PASS / FAIL
Notes: _______________________

SCENARIO 4 - Approval:
[ ] Approve works: PASS / FAIL
[ ] Role updated: PASS / FAIL
[ ] Email sent: PASS / FAIL
Notes: _______________________

SCENARIO 5 - Dashboard Access:
[ ] Dashboard loads: PASS / FAIL
[ ] Features work: PASS / FAIL
Notes: _______________________

OVERALL RESULT: PASS / FAIL
```

---

## 🚀 Quick Test (5 Minutes)

**Minimum viable test:**

1. **Go to:** `http://localhost/wordpress/coach-role-request`
2. **Login** if prompted
3. **Fill form** with test data
4. **Submit**
5. **Check:** No SQL error + Success message

If ✅ = **FIX WORKS!**  
If ❌ = Check troubleshooting section

---

**Ready to test! Good luck!** 🎉
