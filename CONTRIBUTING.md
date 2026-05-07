# Contributing to Sentinel

Thank you for your interest in contributing to Sentinel! This document provides guidelines for contributing to the project.

## Getting Started

1. Fork the repository
2. Clone your fork
3. Create a feature branch: `git checkout -b feature/your-feature`
4. Make your changes
5. Test locally with Docker: `docker compose up -d`
6. Submit a pull request

## Development Setup

```bash
# Start the environment
docker compose up -d

# View logs
docker compose logs -f app

# Access the database
docker compose exec db psql -U sentinel -d sentinel

# Rebuild after changes
docker compose up -d --build
```

## Code Standards

- **PHP**: PSR-12 coding standard
- **JavaScript**: ES6+ with JSDoc comments
- **CSS**: BEM-inspired naming with CSS custom properties
- **SQL**: Uppercase keywords, snake_case identifiers

## Adding a New Rule

1. Create a new class in `app/Engine/Rules/` implementing `RuleInterface`
2. Add the rule to the `loadRules()` method in `app/Engine/RiskEngine.php`
3. Insert a database entry in `database/migrations/001_initial_schema.sql`
4. Map the rule slug to a category in `app/Engine/ScoreCalculator.php`

## Pull Request Guidelines

- Write clear, descriptive commit messages
- Include tests for new functionality
- Update documentation for API changes
- One feature/fix per PR

## Reporting Issues

- Use GitHub Issues for bug reports
- Include steps to reproduce
- Include PHP and PostgreSQL version information
- Include relevant log output

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
