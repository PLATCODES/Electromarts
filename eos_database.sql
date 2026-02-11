-- Create database
CREATE DATABASE IF NOT EXISTS eos_database;
USE eos_database;

-- 1. Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Devices table
CREATE TABLE devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    brand VARCHAR(50),
    model VARCHAR(50),
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Order Items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    device_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- 5. Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    device_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- 7. System Logs table (for admin to track activities)
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Computers', 'Laptops, Desktops, and Computer accessories'),
('Phones', 'Smartphones and feature phones'),
('Audio', 'Headphones, Speakers, and audio equipment'),
('Gaming', 'Gaming consoles and accessories'),
('Tablets', 'Tablets and e-readers'),
('Accessories', 'Cables, chargers, and other accessories');

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password, role, full_name, phone) VALUES
('admin', 'admin@eos.com', '$2y$10$YourHashedPasswordHere', 'admin', 'System Administrator', '+255123456789'),
('john', 'john@example.com', '$2y$10$YourHashedPasswordHere', 'user', 'John Doe', '+255987654321');

-- Insert sample devices
INSERT INTO devices (name, description, category, brand, model, price, stock) VALUES
('Laptop Pro', 'High performance laptop with 16GB RAM, 512GB SSD', 'Computers', 'TechBrand', 'LP-2023', 1200.00, 15),
('Smartphone X', 'Latest smartphone with 5G and 128GB storage', 'Phones', 'PhoneCo', 'SX-2023', 800.00, 25),
('Wireless Headphones', 'Noise cancelling headphones with 30hr battery', 'Audio', 'SoundPro', 'WH-100', 150.00, 40),
('Gaming Console Pro', 'Next-gen gaming console with 1TB storage', 'Gaming', 'GameTech', 'GC-Pro', 500.00, 10),
('Tablet Mini', 'Compact tablet with 10-inch display', 'Tablets', 'TabCo', 'TM-10', 300.00, 20),
('Smart Watch', 'Fitness tracker with heart rate monitor', 'Accessories', 'WearTech', 'SW-2023', 200.00, 30);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_devices_category ON devices(category);
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_items_order_id ON order_items(order_id);
CREATE INDEX idx_reviews_device_id ON reviews(device_id);
