-- Create database if not exists
CREATE DATABASE IF NOT EXISTS allied_steel_dms;
USE allied_steel_dms;

-- User roles table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- Insert default roles
INSERT INTO roles (role_name) VALUES 
('Admin'), 
('Manager'), 
('Salesperson'), 
('Inventory Manager');

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, email, full_name, role_id) VALUES 
('admin', '$2y$10$8KcnNdVsD.X4LO2zKjkEv.mYyTo1Q5qjnKoESKnmwZkPxM6.zLvtS', 'admin@alliedsteel.com', 'System Administrator', 1);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    zip_code VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    vendor_id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100),
    zip_code VARCHAR(20),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory categories
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inventory items table
CREATE TABLE IF NOT EXISTS inventory (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    item_number VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    unit_of_measure VARCHAR(20) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    minimum_stock DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Media Table for inventory items 
CREATE TABLE media (
    media_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Stock Logs Table
CREATE TABLE IF NOT EXISTS stock_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    type ENUM('addition', 'reduction') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Raw Materials Table (for products purchased from vendors)
CREATE TABLE IF NOT EXISTS raw_materials (
    material_id INT PRIMARY KEY AUTO_INCREMENT,
    material_code VARCHAR(50) NOT NULL UNIQUE,
    material_name VARCHAR(150) NOT NULL,
    description TEXT,
    unit_of_measure VARCHAR(20) NOT NULL,
    current_stock DECIMAL(10,2) DEFAULT 0,
    minimum_stock DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Raw Material Stock Logs Table
CREATE TABLE IF NOT EXISTS raw_material_stock_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    material_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    type ENUM('addition', 'reduction') NOT NULL,
    reason VARCHAR(255) NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Purchases Table
CREATE TABLE IF NOT EXISTS purchases (
    purchase_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_number VARCHAR(50) NOT NULL UNIQUE,
    vendor_id INT NOT NULL,
    purchase_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    delivery_status ENUM('pending', 'in_transit', 'delivered', 'delayed') DEFAULT 'pending',
    expected_delivery DATE DEFAULT NULL,
    invoice_file VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vendor_id) REFERENCES vendors(vendor_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Add index for reporting & filtering
CREATE INDEX idx_vendor_date ON purchases(vendor_id, purchase_date);

-- Purchase Details Table
CREATE TABLE IF NOT EXISTS purchase_details (
    purchase_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    purchase_id INT NOT NULL,
    material_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) DEFAULT 0,
    total_price DECIMAL(12,2) NOT NULL,
    
    FOREIGN KEY (purchase_id) REFERENCES purchases(purchase_id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES raw_materials(material_id) ON DELETE CASCADE
);

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    sale_id INT PRIMARY KEY AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    sale_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    invoice_file VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Sale details table
CREATE TABLE IF NOT EXISTS sale_details (
    sale_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id)
);

-- Password reset table
CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(50) NOT NULL, -- e.g., cash, bank_transfer, check, credit_card
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE
);


DELIMITER //

-- Update raw materials stock after purchase (CORRECTED)
DROP TRIGGER IF EXISTS after_purchase_detail_insert//
CREATE TRIGGER after_purchase_detail_insert
AFTER INSERT ON purchase_details
FOR EACH ROW
BEGIN
    UPDATE raw_materials
    SET current_stock = current_stock + NEW.quantity
    WHERE material_id = NEW.material_id;
END //

-- Update inventory stock after sale
DROP TRIGGER IF EXISTS after_sale_detail_insert//
CREATE TRIGGER after_sale_detail_insert
AFTER INSERT ON sale_details
FOR EACH ROW
BEGIN
    UPDATE inventory
    SET current_stock = current_stock - NEW.quantity
    WHERE item_id = NEW.item_id;
END //

DELIMITER ;

