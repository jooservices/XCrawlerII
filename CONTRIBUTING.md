# Contributing to XCrawlerII

First off, thanks for taking the time to contribute! 🎉

The following is a set of guidelines for contributing to XCrawlerII. These are mostly guidelines, not rules. Use your best judgment, and feel free to propose changes to this document in a pull request.

## Code Style

We follow the **PSR-12** coding standard and use **Laravel Pint** to enforce style.

Before submitting a Pull Request, run the formatter:

```bash
composer format
```

This will automatically fix most style issues using `pint` and `phpcbf`.

## Pull Request Process

1.  **Fork** the repo and create your branch from `develop`.
2.  If you've added code that should be tested, add tests.
3.  Ensure the test suite passes (`composer test`).
4.  Run static analysis (`composer analyse`) to ensure no new errors are introduced.
5.  Issue that pull request!

## Project Structure

Please familiarize yourself with the [Code Structure](docs/architecture/code_structure.md) before making major changes. We use a Modular Monolith pattern (`Modules/`), so identify which module your change belongs to.

## Reporting Bugs

Bugs are tracked as GitHub issues. When creating an issue, please explain the problem and include additional details to help maintainers reproduce the problem:

-   Use a clear and descriptive title for the issue to identify the problem.
-   Describe the exact steps which reproduce the problem in as many details as possible.
-   Provide specific examples to demonstrate the steps.
