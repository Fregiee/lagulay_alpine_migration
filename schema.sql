CREATE TABLE users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(50) NOT NULL,
    type INT NOT NULL,
    suspension INT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products(
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    image VARCHAR(50) NOT NULL,
    price VARCHAR(50) NOT NULL,
    added_by INT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE transactions(
    id INT AUTO_INCREMENT PRIMARY KEY,
    adminId INT NOT NULL,
    customerId INT NOT NULL,
    product_list VARCHAR(50) NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders(
    id INT AUTO_INCREMENT PRIMARY KEY,
    customerId INT NOT NULL,
    money INT NOT NULL,
    product_list VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);