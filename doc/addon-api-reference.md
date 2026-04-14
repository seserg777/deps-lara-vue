# Addon API — protocol reference

Documentation version: **2.3.0** (2024-09-01). Original: [addon_api_documentation.htm](https://www.webdesigner-profi.de/joomla-webdesign/example/addon_api_documentation.htm).

## General rules

- **Method:** `POST` to `{BASE_URL}/index.php?option=com_jshopping&controller=addon_api`
- **In this project:** only the site base URL is set in `.env` — `REMOTE_API_URL` (e.g. `https://deps.ua`). The path `index.php?option=…&controller=…` is taken from [`config/catalog.php`](../config/catalog.php).
- **Body:** `application/x-www-form-urlencoded` (`http_build_query`), fields below.
- **Authorization headers:**
  - First request (token acquisition): `Authorization: Basic base64(email:password)`
  - Afterwards: `Authorization: Bearer {token}`
- **Request parameters:**

| Field   | Type   | Default | Description        |
| ------- | ------ | ------- | ------------------ |
| section | string | —       | API section        |
| task    | string | —       | Action             |
| format  | string | json    | Response format    |
| args    | array  | —       | Arguments for task |

- **Response (JSON):** `status`, `code`, `report`, `result`. Success when `status === "ok"`.

## connection

| task  | args | result          |
| ----- | ---- | --------------- |
| open  | —    | string (token)  |
| info  | —    | array           |
| close | —    | bool            |
| user  | —    | array           |

After `open`, the token is valid for a limited time (often ~60 min; extended on activity). Call `close` when finished.

## category

| task      | args | result |
| --------- | ---- | ------ |
| ids       | —    | array (ids) |
| item      | `id` int | array |
| items     | `ids` int[] | array |
| tree      | —    | array (category tree) |
| list      | `limit` int (0), `limitstart` int (0) | array |
| listCount | —    | int |

## product

Main tasks: `ids`, `item` (`id`, `attributes`), `items`, `list`, `listCount`, `group` (`group`), `search`, `searchInfo`, `edit`, etc. — see the full table in the original documentation.

## cart, checkout, order, shop, user, wishlist, content, manufacturer, addon

The list of tasks and arguments matches the [official documentation](https://www.webdesigner-profi.de/joomla-webdesign/example/addon_api_documentation.htm) (section tables: cart, checkout, order, shop, user, wishlist, content, manufacturer, addon).

## Error codes (selection)

See the Reports section in the original. Example groups: `connection_error` (missing header, invalid token, expired token, etc.), `request_error` (missing section/task, invalid arguments), `category_error`, `product_error`, `cart_error`, `checkout_error`, etc.

## Formats

- `json` (default)
- `var_dump` — debugging
