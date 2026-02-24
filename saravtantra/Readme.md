# 🕉️ Sarvatantra Project (tara)

A PHP & MySQL based web application that runs using XAMPP (Apache + MySQL).

---

## 📌 Requirements

- XAMPP (Latest Version Recommended)
- Web Browser (Chrome / Edge / Firefox)
- PHP 7.x or above (comes with XAMPP)
- MySQL (comes with XAMPP)

---

# 🚀 Installation & Setup Guide

Follow the steps carefully to run the project successfully.

---

## Step 1: Download & Install XAMPP

1. Download XAMPP from the official website:
   https://www.apachefriends.org/

2. Install XAMPP normally:
   - Click Next
   - Select Apache & MySQL
   - Choose installation folder (Default recommended)
   - Click Install

3. After installation, open **XAMPP Control Panel**

---

## Step 2: Extract Project in htdocs Folder

1. Extract the project ZIP file.
2. Copy the extracted folder named:

   ```
   tara
   ```

3. Paste it inside:

   ```
   C:\xampp\htdocs\
   ```

Final project path should be:

```
C:\xampp\htdocs\tara\
```

---

## Step 3: Start Apache & MySQL

1. Open XAMPP Control Panel
2. Click **Start** on:
   - Apache
   - MySQL

Make sure both services show green status.

---

## Step 4: Create Database

1. Open browser and go to:

   ```
   http://localhost/phpmyadmin
   ```

2. Click on **New**
3. Enter database name:

   ```
   sarvatantra_db
   ```

4. Click **Create**

---

## Step 5: Import Database File

1. Select database:

   ```
   sarvatantra_db
   ```

2. Click **Import**
3. Click **Choose File**
4. Select SQL file from:

   ```
   tara/sql/sarvatantra_db.sql
   ```

5. Scroll down and click **Import**

Wait for success message.

---

## Step 6: Run the Project

Open this link in browser:

```
http://localhost/tara/
```

---

# ⚙️ Database Configuration (If Needed)

If database connection file exists, make sure it has:

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "sarvatantra_db";
```

---

# ✅ Troubleshooting

If project is not running:

- Check Apache and MySQL are running
- Check folder name is exactly `tara`
- Check database name is `sarvatantra_db`
- Check SQL file imported successfully
- Restart XAMPP

---

# 📁 Project Information

- Project Folder: `tara`
- Database Name: `sarvatantra_db`
- SQL File Path: `tara/sql/sarvatantra_db.sql`
- Run URL: `http://localhost/tara/`

---

# 👨‍💻 Developed For

Sarvatantra Web Application

---

✔️ Now your project is ready to use.
