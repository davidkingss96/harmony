# 🎸 Harmony - Guitar Improvisation Guide

Guía visual de improvisación para guitarra. Visualiza las notas más relevantes de una progresión de acordes o escalas sobre el diapasón.

## 🎯 ¿Qué hace?

Seleccionas varios acordes o escalas, y la aplicación genera automáticamente un **mapa de influencia** donde las notas que más se repiten destacan visualmente. No necesitas saber teoría musical.

```
Bm → B D F#
G  → G B D
D  → D F# A
Em → E G B

Resultado:
B = 3 apariciones (máxima influencia)
D = 3 apariciones
F# = 2
G = 2
A = 1
E = 1
```

## 🚀 Stack Tecnológico

| Tecnología | Uso |
|------------|-----|
| 🐘 PHP 8 | Backend / API REST |
| 🗄️ MySQL | Base de datos |
| 🎨 JavaScript | Frontend / SVG |
| 🐳 Docker | Entorno de desarrollo |

## 📦 Instalación

```bash
# Clonar el repositorio
git clone https://github.com/tu-usuario/harmony.git
cd harmony

# Levantar contenedores
docker-compose up -d

# Acceder
http://localhost:8080/frontend/
```

## 🗂️ Estructura del Proyecto

```
harmony/
├── api/                    # Backend PHP
│   ├── config/            # Conexión a BD
│   ├── models/            # HarmonyEngine
│   └── index.php          # Router API
├── frontend/               # Frontend
│   ├── css/               # Estilos
│   ├── js/                # Lógica JS
│   └── index.html         # Interfaz
├── database/               # Schema SQL
├── docker-compose.yml
└── Dockerfile
```

## 🔌 API Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/index.php?endpoint=notes` | Notas cromáticas |
| GET | `/api/index.php?endpoint=chords` | Acordes disponibles |
| GET | `/api/index.php?endpoint=scales` | Escalas disponibles |
| POST | `/api/index.php?endpoint=sessions` | Crear sesión |
| POST | `/api/index.php?endpoint=harmony` | Calcular mapa |

## 🎼 Cómo Funciona

1. **Selecciona** acordes o escalas de la lista
2. **Agrega** raíz (C, D, E, etc.) y tipo (Mayor, Menor, etc.)
3. **Haz clic** en "Calcular Mapa"
4. **Visualiza** el diapasón con colores según la influencia de cada nota

## 🎨 Intensidad Visual

| Color | Influencia |
|-------|------------|
| 🔴 Rojo | 80-100% (más importante) |
| 🟠 Naranja | 60-79% |
| 🟡 Amarillo | 40-59% |
| 🟢 Verde | 20-39% |
| ⚫ Gris | 0-19% |

## 📝 Licencia

MIT

---

"Hecho con 🎸 y ☕ para guitarristas que quieren improvisar sin complicarse la vida."
