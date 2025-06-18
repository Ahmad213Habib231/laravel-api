🚀 How to Run the Cashy Project

1️⃣ Requirements
Make sure you have the following installed on your system:

-PHP 8.2 or later
-Composer (PHP package manager)
-MySQL or any supported database
-Laravel 11 (installed with the project)

2️⃣ Steps to Run the Project

1.Clone the project from GitHub:
git clone https://github.com/Yasmin-7-aly/Cashy.git
cd Cashy

2.Install required dependencies:
composer install
npm install 

3.Create the .env file:
cp .env.example .env

4.Generate the application encryption key:
php artisan key:generate

5.Set up the database:
Create a database in MySQL
Edit the .env file and add your database connection details

6.Run migrations to create database tables:
php artisan migrate

7.Start the local server:
php artisan serve
