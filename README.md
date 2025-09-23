# BookClub

BookClub is a Symfony 6 project built as a portfolio showcase.
It simulates a real-world online platform where users can manage their personal library, write reviews, and participate in book clubs with forums and internal reviews.

## Features
### User Management

- Registration with validation (name, username, email, password).
- Default profile image and automatic role assignment (ROLE_USER).
- Login & logout via Symfony Security.
- Editable user profiles, public and private view.

### Books & Reviews

- Add/remove books from personal library.
- Write reviews with star ratings (1–5).
- Homepage sections:
  - Latest Reviews: 12 most recent, approved reviews (carousel).
  - Recent Books: 12 most recent books (carousel).
  - Top 3 Books: books with the most 5-star reviews.
  - Clubs: 5 most popular clubs 

### Clubs

- Create clubs with name, description, and genre.
- Join or leave clubs freely.
- Add books to a club (with optional genre validation).
- Internal club reviews (visible only to members).
- Forums for discussions:
  - General Club Forum.
  - Per-Book Forum (nested replies supported).

## UI/UX

- Bootstrap 5 integration.
- Flashes with auto-hide for feedback.
- Dynamic confirmation modals (buttons styled by action type).
- Responsive carousels with clean card layouts.

## Architecture
### Entities

- Book – basic book metadata (title, author, year, cover image).
- Club – groups of users around specific genres.
- ClubBook – pivot table linking clubs and books.
- ClubBookPost – forum posts at book-level inside clubs.
- ClubPost – forum posts at club-level (general).
- Genre – represents a literary genre (e.g. Fantasy, History, Sci-Fi).
- Review – user reviews for books (rating, content, date).
- User – core user entity with authentication.
- UserBook – relation between users and their libraries.

### Services

- S3Uploader – handles AWS S3 file uploads.

### Security

- LoginFormAuthenticator – manages login flow with CSRF, remember me, and redirects.

## Testing

Testing is done with PHPUnit.
We focus on unit tests for controllers, services, and security:

### Controllers:

- AdminGenreController.
- AdminUserController.
- AdminReviewController.
- BookController.
- ClubController.
- HomeController.
- RegistrationController.
- ReviewController.
- SecurityController (login page, error display).
- UserController.


### Service:

- S3Uploader tested with a mocked S3Client.

### Security:

- LoginFormAuthenticator tested for authentication flow, redirects, and login URL.

Tests include mocks for EntityManager, Repositories, AuthenticationUtils, and S3 client.
Ensures code reliability without hitting the database or AWS.

## Run tests with:
```
php bin/phpunit
```
## Tech Stack

- Framework: Symfony 6
- Database: MySQL / Doctrine ORM
- Frontend: Twig + Bootstrap 5
- File Storage: AWS S3 (via S3Uploader service)
- Testing: PHPUnit + Mocking

## Installation

### Clone the repository:
```
git clone https://github.com/sergiozaragoza17/BookClub.git
cd BookClub
```

### Install dependencies:
```
composer install
```

### Configure .env with database + AWS S3 credentials.
- Copy `.env` to `.env.local` and fill in your AWS credentials
- The application requires AWS S3 to store profile images and book covers.
### Run migrations and fixtures:
```
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### Start the server:
```
symfony serve
```
## Portfolio Context

This project was developed to showcase:

- Symfony best practices (controllers, services, entities).
- Authentication & Security.
- Realistic service integration (AWS S3).
- Unit testing with PHPUnit.
- Clean UI/UX with Bootstrap and Twig.
