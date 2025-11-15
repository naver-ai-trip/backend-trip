# Voyagenius

A comprehensive travel planning and management platform built with Laravel and React. Voyagenius helps users plan trips, collaborate with friends, discover places, book hotels, and document their travel experiences.

## Features

### Trip Management
- Create and manage trips with destinations, dates, and status tracking
- Collaborative trip planning with participants
- Trip sharing via secure tokens
- Trip diaries for documenting experiences
- Checklist management for trip preparation
- Itinerary planning with day-by-day scheduling

### Places & Discovery
- Search places using NAVER Maps API
- Nearby place discovery with geolocation
- Place reviews and ratings
- Favorite places collection
- Place tagging system
- Map checkpoints with images

### Hotel Booking
- Hotel search via Amadeus API
- Hotel offers and pricing
- Hotel ratings and reviews
- Booking management

### Translation & Communication
- Text translation using NAVER Papago
- Image translation (OCR + Translation)
- Speech-to-text translation using Clova Speech
- Translation history management

### Maps & Navigation
- Geocoding and reverse geocoding
- Route planning with directions
- Multi-waypoint route optimization
- Interactive map checkpoints

### Analytics & Insights
- Search trend analysis using NAVER DataLab
- Keyword popularity tracking
- Demographic insights
- Seasonal travel insights
- Destination popularity analysis

### Social Features
- Comments on trips and places
- Favorites system
- Tagging system
- Notifications
- User profiles

### Content Moderation
- Automatic image moderation using NAVER Green-Eye
- Adult and violence content detection
- Queue-based image processing

## Tech Stack

### Backend
- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Authentication**: Laravel Fortify, Laravel Sanctum
- **Admin Panel**: Filament 4
- **API Documentation**: L5-Swagger (OpenAPI/Swagger)
- **Queue**: Redis (Predis)
- **Storage**: Bunny Storage
- **Testing**: PHPUnit

### Frontend
- **Framework**: React 19
- **Language**: TypeScript
- **UI Framework**: Inertia.js
- **Styling**: Tailwind CSS 4
- **UI Components**: Radix UI, Headless UI
- **Build Tool**: Vite
- **Icons**: Lucide React

### External Services
- **Amadeus API**: Hotel search and booking
- **NAVER Cloud Platform**:
  - Maps API (POI search, geocoding, directions)
  - Papago Translation API
  - Clova OCR API
  - Clova Speech API
  - Search Trend API (DataLab)
  - Green-Eye API (Content moderation)
- **Google OAuth**: Social authentication

## Requirements

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+ or PostgreSQL 13+
- Redis
- Web server (Apache/Nginx) or PHP built-in server

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Voyagenius
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure environment variables**
   
   Edit `.env` file and configure:
   - Database credentials
   - Redis configuration
   - External API credentials (see [Configuration](#configuration))

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Seed database (optional)**
   ```bash
   php artisan db:seed
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   composer run dev
   ```

   Or run services separately:
   ```bash
   php artisan serve
   php artisan queue:listen
   npm run dev
   ```

## Configuration

### Database
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=voyagenius
DB_USERNAME=root
DB_PASSWORD=
```

### Redis
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Amadeus API
```env
AMADEUS_API_KEY=your_api_key
AMADEUS_API_SECRET=your_api_secret
AMADEUS_BASE_URL=https://test.api.amadeus.com/v1
AMADEUS_ENABLED=true
```

### NAVER Cloud Platform
```env
# NAVER Maps API
NAVER_MAPS_CLIENT_ID=your_client_id
NAVER_MAPS_CLIENT_SECRET=your_client_secret
NAVER_MAPS_ENABLED=true

# NAVER Papago Translation
NAVER_PAPAGO_CLIENT_ID=your_client_id
NAVER_PAPAGO_CLIENT_SECRET=your_client_secret
NAVER_PAPAGO_ENABLED=true

# NAVER Clova OCR
NAVER_CLOVA_OCR_URL=your_ocr_url
NAVER_CLOVA_OCR_SECRET_KEY=your_secret_key
NAVER_OCR_ENABLED=true

# NAVER Clova Speech
NAVER_CLOVA_SPEECH_URL=your_speech_url
NAVER_CLOVA_SPEECH_SECRET_KEY=your_secret_key
NAVER_SPEECH_ENABLED=true

# NAVER Search Trend API
NAVER_SEARCH_TREND_CLIENT_ID=your_client_id
NAVER_SEARCH_TREND_CLIENT_SECRET=your_client_secret
NAVER_SEARCH_TREND_ENABLED=true

# NAVER Green-Eye (Content Moderation)
NAVER_GREENEYE_URL=your_greeneye_url
NAVER_GREENEYE_SECRET_KEY=your_secret_key
NAVER_GREENEYE_ENABLED=true

# NAVER Local Search API
NAVER_DEVELOPERS_CLIENT_ID=your_client_id
NAVER_DEVELOPERS_CLIENT_SECRET=your_client_secret
NAVER_LOCAL_SEARCH_ENABLED=true
```

### Google OAuth
```env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
```

### Bunny Storage (Optional)
```env
BUNNY_STORAGE_ZONE=your_zone
BUNNY_STORAGE_KEY=your_key
BUNNY_STORAGE_REGION=your_region
```

## API Documentation

API documentation is available via Swagger/OpenAPI. After starting the server, visit:

```
http://localhost:8000/api/documentation
```

To regenerate API documentation:
```bash
php artisan l5-swagger:generate
```

## Development

### Running Tests
```bash
composer test
# or
php artisan test
```

### Code Formatting
```bash
# PHP
./vendor/bin/pint

# TypeScript/React
npm run format
npm run lint
```

### Database Migrations
```bash
# Create migration
php artisan make:migration create_example_table

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback
```

### Queue Workers
```bash
# Process queued jobs
php artisan queue:work

# Process failed jobs
php artisan queue:retry all
```

## Project Structure

```
Voyagenius/
├── app/
│   ├── Actions/          # Fortify actions
│   ├── Exceptions/       # Custom exceptions
│   ├── Filament/         # Admin panel resources
│   ├── Http/
│   │   ├── Controllers/  # API controllers
│   │   ├── Middleware/   # Custom middleware
│   │   ├── Requests/     # Form request validation
│   │   └── Resources/    # API resources
│   ├── Jobs/             # Queue jobs
│   ├── Models/           # Eloquent models
│   ├── Policies/         # Authorization policies
│   ├── Providers/        # Service providers
│   └── Services/         # Business logic services
│       ├── Amadeus/      # Amadeus API integration
│       └── Naver/        # NAVER API integrations
├── database/
│   ├── factories/        # Model factories
│   ├── migrations/       # Database migrations
│   └── seeders/          # Database seeders
├── resources/
│   ├── js/               # React/TypeScript frontend
│   │   ├── components/   # React components
│   │   ├── hooks/        # Custom React hooks
│   │   ├── layouts/      # Page layouts
│   │   ├── pages/        # Inertia pages
│   │   └── types/        # TypeScript types
│   └── views/            # Blade templates
├── routes/
│   ├── api.php           # API routes
│   └── web.php           # Web routes
└── tests/                # PHPUnit tests
```

## Key Models

- **Trip**: Core trip entity with destinations, dates, and participants
- **Place**: Places/POIs from NAVER Maps
- **TripDiary**: Diary entries for trips
- **ItineraryItem**: Day-by-day itinerary items
- **MapCheckpoint**: Map markers with coordinates
- **ChecklistItem**: Trip preparation checklists
- **Review**: Reviews for trips and places
- **Comment**: Comments on various entities
- **Favorite**: Favorites system
- **Tag**: Tagging system
- **Translation**: Translation history
- **Notification**: User notifications
- **Share**: Trip sharing functionality

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For issues, questions, or contributions, please open an issue on the GitHub repository.

## Acknowledgments

- [Laravel](https://laravel.com)
- [React](https://react.dev)
- [Inertia.js](https://inertiajs.com)
- [Filament](https://filamentphp.com)
- [Amadeus API](https://developers.amadeus.com)
- [NAVER Cloud Platform](https://www.ncloud.com)

