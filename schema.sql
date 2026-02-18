CREATE DATABASE IF NOT EXISTS gaming_store;
USE gaming_store;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sale items table
CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed categories
INSERT INTO categories (name) VALUES
('Graphics Cards'),
('Processors'),
('RAM'),
('Storage'),
('Monitors'),
('Peripherals');

-- Seed sample products
INSERT INTO products (category_id, name, description, price, stock_quantity, image_url) VALUES
(1, 'NVIDIA RTX 4090', 'Flagship GPU with 24GB GDDR6X', 62900.00, 8, ''),
(1, 'AMD RX 7900 XTX', 'High-end AMD GPU with 24GB GDDR6', 42900.00, 12, ''),
(2, 'Intel Core i9-14900K', '24-core flagship processor', 22900.00, 15, ''),
(2, 'AMD Ryzen 9 7950X', '16-core high-performance CPU', 21500.00, 10, ''),
(3, 'Corsair Vengeance DDR5 32GB', 'DDR5-6000 CL36 dual kit', 5490.00, 25, ''),
(3, 'G.Skill Trident Z5 RGB 32GB', 'DDR5-6400 premium RGB RAM', 6290.00, 18, ''),
(4, 'Samsung 990 Pro 2TB', 'NVMe Gen4 SSD', 7490.00, 20, ''),
(4, 'WD Black SN850X 1TB', 'PCIe Gen4 NVMe SSD', 3990.00, 30, ''),
(5, 'ASUS ROG Swift PG27AQN', '27" 1440p 360Hz IPS monitor', 32900.00, 5, ''),
(5, 'LG 27GP950-B', '27" 4K 160Hz Nano IPS', 24900.00, 7, ''),
(6, 'Logitech G Pro X Superlight 2', 'Wireless gaming mouse 60g', 4690.00, 35, ''),
(6, 'Razer Huntsman V3 Pro', 'Analog optical gaming keyboard', 8990.00, 3, '');
