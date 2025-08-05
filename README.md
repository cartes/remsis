# 🧾 Remsis - Sistema de Remuneraciones Modular

Remsis es un sistema de remuneraciones desarrollado en **Laravel 12** con arquitectura modular gracias a [nWidart/laravel-modules](https://github.com/nWidart/laravel-modules).

El objetivo del sistema es ofrecer una plataforma escalable para la administración de remuneraciones, empleados, perfiles y control contable, adaptable a las leyes chilenas y ampliable a otras legislaciones.

---

## 🛠️ Tecnologías utilizadas

- Laravel 12
- Laravel Modules (nWidart)
- Tailwind CSS
- Vite
- Alpine.js
- Composer
- PHP 8.2+
- MySQL / MariaDB

---

## 📦 Estructura de Módulos

Actualmente el sistema cuenta con los siguientes módulos:

- `AdminPanel` – Panel principal de administración
- `Core` – Configuración central del sistema
- `Employees` – Gestión de empleados
- `Payroll` – Módulo de remuneraciones
- `Users` – Administración de usuarios internos (roles, permisos, etc.)

---

## ⚙️ Requisitos

- PHP >= 8.2
- Composer
- Node.js + npm
- Base de datos MySQL/MariaDB

---

## 🚀 Instalación

```bash
git clone https://github.com/tuusuario/remsis.git
cd remsis
composer install
npm install && npm run dev
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
