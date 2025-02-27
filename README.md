# Query Your Quote

A modern web application that generates random quotes and displays performance metrics in real-time. Built with Laravel 12, Inertia.js, and React.

![Query Your Quote](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)

## About Query Your Quote

Query Your Quote is a web application that demonstrates modern web development practices using Laravel and React. The application:

- Fetches random quotes from the DummyJSON API
- Displays quotes with author attribution
- Shows real-time request performance metrics with a dynamic speedometer
- Provides user authentication and profile management
- Demonstrates proper API integration and error handling

## Tech Stack

- **Backend**: Laravel 12 with PHP 8.4
- **Frontend**: React 18 with Inertia.js
- **Styling**: Tailwind CSS
- **Development**: Docker, Vite
- **API Integration**: DummyJSON API
- **Authentication**: Laravel Breeze

## Getting Started

### Prerequisites

- Docker Engine installed and running
- Git

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/sandergroenen/query-your-quote.git
   cd query-your-quote
   ```

2. Run the start script:
   ```bash
   chmod +x scripts/start-local.sh
   ./scripts/start-local.sh
   ```

That's it! The script will:
- Build and start Docker containers
- Install all dependencies
- Set up the environment
- Run database migrations and seeders
- Start the development server

### Accessing the Application

Once the setup is complete, you can access the application at:
- **URL**: http://localhost
- **Default credentials**:
  - Email: user@example.com
  - Password: password

## Features

- **Quote Generation**: Get random quotes with a single click
- **Performance Metrics**: View request time metrics with a dynamic speedometer
- **User Authentication**: Register, login, and manage your profile
- **Responsive Design**: Works on desktop and mobile devices

## Development

### Project Structure

- `app/` - Laravel application code
- `resources/js/` - React components and pages
- `resources/js/Components/Quote/` - Quote-related components
- `routes/` - API and web routes
- `scripts/` - Utility scripts for development

### Key Components

- `RandomQuote.jsx` - Main component for displaying quotes
- `DummyJsonService.php` - Service for interacting with the DummyJSON API
- `QuoteController.php` - API controller for quote-related endpoints

## License

The Query Your Quote application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
