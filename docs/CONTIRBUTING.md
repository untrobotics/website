# Contributing to UNT Robotics Website

Thank you for considering contributing to the UNT Robotics website! This document provides guidelines and instructions for contributing to the project.

## Code of Conduct

All contributors are expected to adhere to our code of conduct:

1. Be respectful and inclusive of all contributors
2. Follow UNT's academic integrity policies
3. Maintain professionalism in communications
4. Report any inappropriate behavior to project maintainers

## Getting Started

1. Fork the repository
2. Clone your fork locally
3. Set up your development environment (see GETTING_STARTED.md)
4. Create a new branch for your feature/fix
5. Make your changes
6. Submit a pull request

## Development Workflow

### 1. Branching Strategy

- `main` - Production branch
- `develop` - Development branch
- Feature branches: `feature/your-feature-name`
- Bugfix branches: `fix/bug-description`

### 2. Commit Messages

Follow conventional commits format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding/modifying tests
- `chore`: Maintenance tasks

Example:

```
feat(merchandise): add size selection to t-shirt orders

- Added size dropdown component
- Integrated with Printful API
- Updated order processing logic

Closes #123
```

### 3. Pull Request Process

1. Update documentation for any new features
2. Add/update tests as needed
3. Ensure all tests pass
4. Update CHANGELOG.md
5. Request review from maintainers
6. Address review feedback

### 4. Code Style Guidelines

#### PHP

- Follow PSR-12 coding standards
- Use type hints where possible
- Document classes and methods with PHPDoc

```php
/**
 * Process a new merchandise order
 *
 * @param int $orderId Order identifier
 * @param array $items List of ordered items
 * @return bool Success status
 */
public function processOrder(int $orderId, array $items): bool
```

#### JavaScript

- Use ES6+ features
- Follow Airbnb JavaScript Style Guide
- Document functions with JSDoc

```javascript
/**
 * Updates the shopping cart total
 * @param {Array} items - Cart items
 * @returns {number} Cart total
 */
function updateCartTotal(items) {
```

#### HTML/CSS

- Use semantic HTML5 elements
- Follow BEM naming convention for CSS
- Maintain responsive design principles

### 5. Testing Guidelines

1. **Unit Tests**

   - Write tests for new features
   - Update existing tests when modifying functionality
   - Aim for high coverage of critical paths

2. **Integration Tests**

   - Test PayPal integration using sandbox
   - Test Discord bot functionality in test server
   - Verify email notifications

3. **Manual Testing**
   - Test on multiple browsers
   - Verify mobile responsiveness
   - Check accessibility compliance

### 6. Documentation

1. **Code Documentation**

   - Document complex logic
   - Explain non-obvious decisions
   - Keep documentation up to date

2. **API Documentation**

   - Document new endpoints
   - Include request/response examples
   - Note any authentication requirements

3. **User Documentation**
   - Update user guides for new features
   - Include screenshots where helpful
   - Document configuration changes

## Areas for Contribution

1. **High Priority**

   - Payment processing improvements
   - Discord integration enhancements
   - Security updates
   - Performance optimization

2. **Feature Requests**

   - Event management system
   - Member dashboard improvements
   - Automated testing
   - Analytics integration

3. **Documentation**
   - API documentation
   - Setup guides
   - User documentation
   - Code comments

## Getting Help

- Join our [Discord server](https://discord.gg/untrobotics)
- Check our [Jira board](https://untrobotics.atlassian.net)
- Check existing issues and discussions
- Contact project maintainers
- Review documentation

## Project Management

We use Jira to track our development work. You can find our project board at [untrobotics.atlassian.net](https://untrobotics.atlassian.net).

1. **Creating Issues**

   - Check existing issues first
   - Use provided templates when available
   - Include clear reproduction steps for bugs
   - Tag appropriate components

2. **Working with Jira**
   - Assign yourself to issues you're working on
   - Update issue status as you progress
   - Link pull requests to issues
   - Add time tracking if applicable

## Security Issues

For security issues:

1. **DO NOT** create a public issue
2. Email webmaster@untrobotics.com
3. Include detailed description
4. Wait for confirmation before disclosure

## License

By contributing, you agree that your contributions will be licensed under the project's license.

## Questions?

If you have questions about contributing:

1. Check existing documentation
2. Search closed issues
3. Ask in Discord
4. Contact maintainers

Thank you for contributing to UNT Robotics!
