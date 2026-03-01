# LocalHelp

**Neighbors helping neighbors** — a map-based platform where people post and find local help requests.

Built for the DEV Weekend Challenge.

## Features

- **Google OAuth2** authentication (no passwords)
- **Interactive map** (OpenStreetMap + Leaflet) with colored markers
- **Help requests** with categories: Products, Medicine, Transport, Other
- **Draw area** on the map to filter visible requests
- **Real-time updates** via Laravel Reverb (WebSockets)
- **Status tracking**: Open → In Progress → Fulfilled
- **Auto-expiration** (configurable, default 24h, max 7 days)
- **Spam prevention**: reCAPTCHA, daily rate limit, keyword blacklist
- **Multilingual**: English and Ukrainian (switchable)
- **Mobile-friendly** responsive design

## Tech Stack

- Laravel 12.x + Livewire v4
- SQLite (zero config)
- TailwindCSS v4
- Leaflet.js + Leaflet Draw
- Laravel Reverb (WebSockets)
- Laravel Socialite (Google OAuth)

## Quick Start

```bash
# Clone and install
git clone <repo-url> localhelp
cd localhelp
composer install
npm install

# Configure
cp .env.example .env
php artisan key:generate
```

Edit `.env` and fill in:
- `GOOGLE_CLIENT_ID` + `GOOGLE_CLIENT_SECRET` ([Google Cloud Console](https://console.cloud.google.com/apis/credentials))
- `RECAPTCHA_SITE_KEY` + `RECAPTCHA_SECRET_KEY` (optional in local dev)

```bash
# Database
touch database/database.sqlite
php artisan migrate --seed

# Build assets
npm run build

# Start servers (3 terminals)
php artisan serve             # App: http://localhost:8000
php artisan reverb:start      # WebSocket server
php artisan queue:listen      # Queue worker (for broadcasts)
```

## Development

```bash
npm run dev                   # Vite dev server with HMR
php artisan serve
php artisan reverb:start
```

## Configuration

All app-specific settings in `config/localhelp.php`:

| Key | Description | Default |
|-----|-------------|---------|
| `locale.default` | Default language | `en` |
| `locale.available` | Available languages | `['en', 'uk']` |
| `spam.daily_limit` | Max requests per user per day | `5` |
| `spam.blacklist` | Blocked keywords | see config |
| `map.default_lat/lng` | Map center | Kyiv |
| `map.default_zoom` | Initial zoom level | `12` |
| `requests.default_expiry_hours` | Default expiration | `24` |
| `requests.max_expiry_days` | Max expiration | `7` |

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `GOOGLE_CLIENT_ID` | Yes | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | Yes | Google OAuth client secret |
| `RECAPTCHA_SITE_KEY` | No* | reCAPTCHA v2 site key |
| `RECAPTCHA_SECRET_KEY` | No* | reCAPTCHA v2 secret key |

\* reCAPTCHA is automatically skipped in `local` environment.

## License

MIT
