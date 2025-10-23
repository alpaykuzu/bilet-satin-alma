# Bus Ticket Sales Platform

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)

This project is a dynamic, multi-user bus ticket sales platform built with core PHP, SQLite, and modern web technologies. The entire application is containerized with Docker for seamless setup and deployment.

The system supports multiple user roles (Passengers, Company Admins, and a Super Admin), each with specific permissions. It emphasizes core web development principles, including database management, user authentication, role-based access control (RBAC), and protection against common web vulnerabilities (SQL Injection, XSS, IDOR).

## Project Setup & Installation

This project is fully containerized. The only prerequisite is to have **Docker** and **Docker Compose** installed on your system.

[**» Download Docker Desktop here**](https://www.docker.com/products/docker-desktop/)

---

### Running the Application

1.  **Clone the Repository**
    ```sh
    git clone https://github.com/alpaykuzu/bilet-satin-alma
    ```

2.  **Navigate to the Project Directory**
    ```sh
    cd bilet-satin-alma/bus-ticket-system
    ```

3.  **Build and Run the Container**
    ```sh
    docker-compose up --build
    ```
    * `--build`: This command builds the Docker image from the `Dockerfile` the first time it's run, installing PHP, Apache, and all required extensions (`pdo_sqlite`, `gd`, `intl`).
    * `up`: This command starts the container.

4.  **Automatic Database Setup**
    The first time the container starts, the `docker-entrypoint.sh` script will automatically:
    * Check if `veritabani.sqlite` exists.
    * If it doesn't, the script will run `setup_database.php` to create the database, all necessary tables, and populate it with sample data (test users, a bus company, and sample trips).

5.  **Access the Application**
    Once the container is running (you'll see Apache logs in your terminal), you can access the platform in your web browser at:

    **[http://localhost:8080](http://localhost:8080)**

To stop the application, return to your terminal and press `Ctrl + C`, then run `docker-compose down`.

## Technology Stack

* **Backend:** Core PHP
* **Database:** SQLite 3
* **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript
* **PDF Generation:** TCPDF
* **QR Code:** PHP QR Code
* **Containerization:** Docker & Docker Compose
* **Web Server:** Apache (provided by the official PHP Docker image)

## Key Features

* **Dynamic Trip Search:** Find trips by departure, destination, and date.
* **Multi-Role User System:** Three distinct user roles with different permissions.
* **Secure Authentication:** Secure user registration and login with password hashing.
* **Visual Seat Selection:** A `2+1` bus layout for users to pick their seats.
* **Balance-Based Payment:** Users have a virtual balance to purchase tickets.
* **Dynamic Coupon System:**
    * Super Admins can create global, multi-use coupon codes.
    * Company Admins can create coupons valid only for their own company's trips.
    * Coupon usage limits are enforced, and canceled tickets correctly restore usage quotas.
* **Dynamic PDF Tickets:** Generate and download PDF tickets, complete with trip details and a scannable QR code.
* **Role-Based Access:** A robust `require_role()` function protects all sensitive pages.
* **Trip Management (Admin):** Company Admins can manage their own trips (CRUD), including advanced features like daily/weekly repeating trip creation.
* **Security:** Built-in protection against SQL Injection (via prepared statements), XSS (via `htmlspecialchars`), and IDOR (via session-based ownership checks).

## User Roles & Test Credentials

The system comes pre-loaded with three test accounts, one for each role.

| Role | Email | Password | Permissions |
| :--- | :--- | :--- | :--- |
| **User (Passenger)** | `user@bilet.com` | `Kullanici123!` | Can search, book, view, cancel, and download PDF tickets. |
| **Company Admin** | `firma@bilet.com` | `FirmaSifre123!` | Manages all trips and coupons **only** for their assigned company. |
| **Super Admin** | `admin@bilet.com` | `AdminSifre123!` | Manages bus companies, creates/assigns Company Admins, and creates global coupons. |

## License

This project is licensed under the MIT License.
