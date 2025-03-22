# Chippy - Personal Finance Management Web Application

## Overview
Chippy is a web-based application designed to help users manage their personal finances effectively. Built with PHP, MySQL, HTML, and CSS, it includes features such as user authentication, transaction tracking, category management, financial analysis, and a basic financial chatbot. This README provides instructions to set up and run the application locally, as well as access a live demo.

## Features
- User registration and login
- Add, edit, delete, and filter income/expense transactions
- Manage personalized income and expense categories
- Financial overview with total income, expenses, and balance
- Basic financial chatbot for budgeting and saving advice
- Responsive UI with a clean, modern design

## Demo
You can explore a live demo of Chippy at:

**[demo.chippycash.com](http://demo.chippycash.com)**

- **Username:** demo_user  
- **Password:** Demo@123  

*Note: The demo is for testing purposes only and may reset periodically.*

## Prerequisites
To run Chippy locally, ensure you have the following installed:
- PHP (version 7.4 or higher)
- MySQL (version 5.7 or higher)
- Web Server (e.g., Apache, Nginx) - XAMPP or WAMP recommended for ease of setup
- Composer (optional, if dependencies are added later)
- A modern web browser (e.g., Chrome, Firefox)

## Installation

### 1. Clone the Repository
Clone this repository to your local machine:

```bash
git clone https://github.com/dmhung1508/ChippyCash.git
```
Or download the ZIP file and extract it to your web server directory.

### 2. Set Up the Database
Create a MySQL database named `chippy`:
```sql
CREATE DATABASE chippy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the database schema:
- Locate the `database.sql` file in the project folder (if provided).
- Run the SQL script in your MySQL client (e.g., phpMyAdmin):
  ```bash
  mysql -u [your-username] -p chippy < database.sql
  ```
- If no `database.sql` file exists, manually create the tables (users, categories, transactions, chat_history) based on the structure in `functions.php`.

### 3. Configure the Application
Navigate to the project folder and locate the configuration file (`config.php`).  
Update the database connection settings:

```php
<?php
$host = 'localhost';
$dbname = 'chippy';
$username = 'root'; // Your MySQL username
$password = '';     // Your MySQL password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

Save the file.

### 4. Set Up the Web Server
Move the project folder to your web server’s root directory:
- **For XAMPP:** `htdocs/chippy`
- **For WAMP:** `www/chippy`
- Start your web server and MySQL services.

### 5. Run the Application
Open your browser and navigate to:

```
http://localhost/chippy
```

Register a new account or log in with an existing one to start using Chippy.

## Project Structure

```
chippy/
├── css/
│   └── style.css       # Main CSS file for styling
├── js/
│   └── main.js         # JavaScript file (if applicable)
├── index.php           # Dashboard and login/registration page
├── auth.php            # Authentication handling (login/register forms)
├── transactions.php    # Transaction management page
├── categories.php      # Category management page
├── profile.php         # User profile page
├── logout.php          # Logout script
├── functions.php       # Core functions for user, transaction, and category management
├── FinancialChatbot.php # Financial chatbot logic
├── config.php          # Database configuration (create this if not present)
└── README.md           # This file
```

## Usage
- **Register/Login:** Start by creating an account or logging in at `index.php`.
- **Manage Transactions:** Add, edit, or delete transactions via `transactions.php`.
- **Manage Categories:** Customize income/expense categories in `categories.php`.
- **Chatbot:** Interact with the financial chatbot for advice (currently basic, under development).
- **Profile:** Update your details in `profile.php`.

## Troubleshooting
- **Database Connection Error:** Ensure MySQL is running and credentials in `config.php` are correct.
- **404 Errors:** Verify the project folder is in the correct web server directory and URL is accurate.
- **CSS Not Loading:** Check the path in `<link rel="stylesheet" href="css/style.css">` matches your folder structure.

## Contributing
Feel free to fork this repository, submit issues, or create pull requests to improve Chippy. Contributions are welcome!

## License
This project is licensed under the MIT License - see the `LICENSE` file for details (if applicable).

## Contact
For questions or support, contact **[admin@dinhmanhhung.net]**.
