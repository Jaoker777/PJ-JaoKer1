# 🎮 Gaming Store Inventory System

ระบบจัดการสต็อกสินค้าร้านขายอุปกรณ์เกมมิ่ง พัฒนาด้วย PHP 8.2 + MariaDB 10.6 + Docker Compose

## Quick Start

```bash
# 1. Build & run
docker-compose up -d --build

# 2. Open the app
# Web: http://localhost:8001
# phpMyAdmin: http://localhost:8080 (root / rootpassword)
```

> Schema จะ import อัตโนมัติเมื่อรัน Docker ครั้งแรก

## Features

- **Dashboard** — สถิติสินค้า, stock ต่ำ, ยอดขายรวม, ยอดขายล่าสุด
- **Products** — เพิ่ม / แก้ไข / ลบ สินค้า, แสดงเตือน stock ต่ำ
- **Sales** — บันทึกการขาย (หัก stock อัตโนมัติ), ประวัติการขาย

## Tech Stack

PHP 8.2 · MariaDB 10.6 · Apache · Docker Compose · PDO · CSS Variables
