# CLAUDE.md — Casa Bot

## Descripción general

Sistema personal modular construido en Laravel con integración de Telegram.
Filosofía: **web para administrar, Telegram para consumir rápido**.

Un solo bot de Telegram con múltiples comandos actúa como interfaz mobile.
La web (Laravel + Livewire + Tailwind) es el panel de administración completo.

---

## Stack técnico

- **Backend**: Laravel 11+
- **Frontend web**: Livewire 3 + Tailwind CSS 3
- **Base de datos**: SQLite (local/dev), MySQL/MariaDB (producción)
- **Bot de Telegram**: `nutgram` (paquete recomendado, soporta conversaciones con estado)
- **IA para matching**: Groq API (llama-3.1-8b-instant, cuota gratuita) — solo usado en módulo de lista del súper
- **Auth**: Laravel Jetstream (Livewire stack)
- **Servidor**: VPS Donweb con dominio propio y HTTPS (webhook mode)
- **Bot mode**: un solo bot, múltiples comandos

---

## Arquitectura general

```
proyecto/
├── app/
│   ├── Http/
│   │   └── Livewire/
│   │       ├── Shopping/
│   │       ├── Meals/
│   │       └── Plants/
│   ├── Models/
│   ├── Services/
│   │   ├── TelegramService.php
│   │   ├── AIMatcherService.php   ← lógica de matching con IA
│   │   └── ShoppingService.php
│   └── Telegram/
│       └── Handlers/
│           ├── ShoppingHandler.php
│           ├── MealsHandler.php
│           └── PlantsHandler.php
├── database/migrations/
└── routes/
    ├── web.php
    └── telegram.php   ← o definido en el ServiceProvider de nutgram
```

---

## Módulos

### 1. Lista del Súper (`shopping`)

**Propósito**: mantener una lista de compras persistente. Los items comprados no se eliminan, se marcan — así no hay que recargarlos en la próxima vuelta.

**Flujo web**:
- Ver lista completa (pendientes arriba, comprados abajo con checkbox deshabilitado)
- Agregar items manualmente
- Marcar/desmarcar como comprado
- Eliminar items definitivamente (opcional)

**Flujo Telegram**:
- `/lista` → devuelve solo los items **pendientes**
- `/agregar [item]` → llama a la IA para deduplicar y crea o reactiva el item
- `/compré [item]` → llama a la IA para identificar el item y lo marca como comprado

**Lógica de IA para matching** (`AIMatcherService`):
- Se construye el listado actual del usuario
- Se manda a la API de Claude con el nuevo item
- Se pide respuesta en JSON estricto:
  ```json
  // Si existe:
  { "action": "match", "id": 14, "name": "leche" }
  // Si no existe:
  { "action": "create", "name": "leche descremada" }
  ```
- El código procesa el JSON y actúa en consecuencia
- Si `action = match` y el item estaba comprado → se reactiva (purchased_at = null)
- Si `action = match` y estaba pendiente → se notifica que ya está en lista
- Si `action = create` → se crea con el nombre sugerido por la IA

---

### 2. Qué Comer Hoy (`meals`)

**Propósito**: banco de comidas personales. Desde Telegram te tira una sugerencia random evitando repetición reciente.

**Flujo web**:
- CRUD completo de comidas
- Ver historial de sugerencias (campo `last_suggested_at`)

**Flujo Telegram**:
- `/comer` → devuelve 1 sugerencia random (excluyendo las sugeridas en los últimos 5 días)

**Lógica anti-repetición**:
- Al sugerir, filtrar `WHERE last_suggested_at IS NULL OR last_suggested_at < NOW() - INTERVAL 5 DAY`
- Si no hay opciones disponibles en ese rango, ampliar automáticamente a 3 días, luego a 1 día
- Actualizar `last_suggested_at` al momento de la sugerencia

**Fase 2 (no implementar todavía)**:
- Tabla `meal_ingredients` para asociar ingredientes a cada comida
- Comando `/ingredientes [comida]` para ver qué se necesita

---

### 3. Riego de Plantas (`plants`)

**Propósito**: log de riego. Simple, con timestamp y comentario opcional. Útil para ver frecuencia y notas de cada planta.

**Flujo web**:
- CRUD de plantas
- Timeline de logs por planta (fecha, comentario)

**Flujo Telegram**:
- `/plantas` → lista las plantas registradas (nombre + id corto o alias)
- `/riego [planta]` → registra log con timestamp actual, sin comentario
- `/riego [planta] [comentario]` → registra log con comentario

**Lógica de identificación de planta**:
- Si el usuario tiene una sola planta → no hace falta especificar, se asume esa
- Si tiene varias → se busca por nombre (LIKE simple, las plantas las carga el usuario desde web con nombres controlados)
- Si no matchea → el bot responde con la lista de plantas para que elija

---

## Modelo de datos

```sql
-- Usuarios
users
  id, name, email, password
  telegram_chat_id (nullable, unique)  ← vincula cuenta web con bot
  created_at, updated_at

-- Lista del súper
shopping_items
  id
  user_id (FK users)
  name
  purchased_at (timestamp, nullable)   ← null = pendiente
  created_at, updated_at

-- Comidas
meals
  id
  user_id (FK users)
  name
  last_suggested_at (timestamp, nullable)
  created_at, updated_at

-- Plantas
plants
  id
  user_id (FK users)
  name
  created_at, updated_at

-- Logs de riego
plant_logs
  id
  plant_id (FK plants)
  notes (text, nullable)
  created_at                            ← este es el timestamp del riego
```

---

## Comandos del bot (referencia completa)

| Comando | Descripción |
|---|---|
| `/lista` | Muestra items pendientes de compra |
| `/agregar [item]` | Agrega item a la lista (con deduplicación por IA) |
| `/compré [item]` | Marca item como comprado (con matching por IA) |
| `/comer` | Sugerencia random de qué comer (evita repetición reciente) |
| `/plantas` | Lista las plantas registradas |
| `/riego [planta]` | Registra riego de la planta especificada |
| `/riego [planta] [comentario]` | Registra riego con nota adicional |
| `/ayuda` | Lista todos los comandos disponibles |

---

## Multiusuario

- Cada recurso (`shopping_items`, `meals`, `plants`) tiene `user_id`
- El bot identifica al usuario por `telegram_chat_id`
- Si el `chat_id` no está vinculado a ningún usuario → el bot responde con instrucciones para registrarse en la web
- La web tiene login estándar de Laravel (Breeze o similar)
- El panel muestra solo los datos del usuario autenticado

---

## Variables de entorno necesarias

```env
# Telegram
TELEGRAM_TOKEN=
TELEGRAM_WEBHOOK_URL=https://tudominio.com/telegram/webhook

# IA (Groq)
AI_API_KEY=
AI_BASE_URL=https://api.groq.com/openai/v1/chat/completions
AI_MODEL_ANALYSIS=llama-3.1-8b-instant
```

---

## Orden de implementación sugerido

1. Instalar Laravel + Breeze (auth básica) + Livewire + Tailwind
2. Agregar campo `telegram_chat_id` a `users`
3. Instalar y configurar `nutgram`, registrar webhook
4. **Módulo Plantas** (el más simple, buen punto de entrada)
   - Migraciones, modelo, Livewire CRUD web
   - Handler de Telegram para `/plantas` y `/riego`
5. **Módulo Comidas**
   - Migraciones, modelo, Livewire CRUD web
   - Handler de Telegram para `/comer`
6. **Módulo Lista del Súper**
   - Migraciones, modelo, Livewire web
   - `AIMatcherService` con llamada a Claude API
   - Handlers de Telegram para `/lista`, `/agregar`, `/compré`
7. Comando `/ayuda` que lista todo
8. Testing manual end-to-end

---

## Notas y decisiones de diseño

- Los items comprados **nunca se eliminan automáticamente** — se marcan con `purchased_at`
- Para reactivar un item comprado desde Telegram, `/agregar` detecta el match y lo reactiva
- `nutgram` se eligió sobre `telegram-bot-sdk` por mejor soporte de conversaciones con estado (útil si en el futuro se agrega flujo de confirmación)
- La IA solo se usa en el módulo de lista — los otros módulos tienen datos controlados que no requieren fuzzy matching
- Webhook mode requiere HTTPS en el servidor — Donweb con SSL cubre este requisito
