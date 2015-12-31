# Contributing to phpSmug

## The Get Going Quick Guide

1. Fork the phpSmug repository on GitHub.
2. If you're submitting a fix or improvement to the phpSmug code or submitting a new example, create a new branch from the `master` branch, for example: `your-fork/some-cool-feature` or `your-fork/fixing-something-broken` branch.
3. If you're submitting a fix or improvement to the phpSmug website or documentation, create a new branch from the `gh-pages` branch.
4. If you're submitting code changes, as opposed to documentation, write tests that fail without your changes and pass with them.
5. Ensure all tests pass locally by running: `vendor/bin/phpunit` within your local clone of the repository.
6. Submit a [pull request](https://help.github.com/articles/using-pull-requests/).

All pull requests will be reviewed and will be merged once all tests have passed.

# Coding Standards

To try and keep things looking :sparkles:, please ensure your changes comply with the following coding standards:

 * [PSR-1: Basic Coding Standard](http://www.php-fig.org/psr/psr-1/)
 * [PSR-2: Coding Style Guide](http://www.php-fig.org/psr/psr-2/)
 * [PSR-4: Improved Autoloading](http://www.php-fig.org/psr/psr-4/)

All contributions are automatically checked against these standards using [php-cs-fixer](http://cs.sensiolabs.org/).

# Licensing

By contributing code you agree to license your contribution under the MIT license.

By contributing documentation, examples, or any other non-code assets you agree to license your contribution under the CC BY 3.0 license. Attribution shall be given according to the current bylaws of this group.
