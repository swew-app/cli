# Agent Guidelines for SWEW/CLI

## Commands
- **Run all tests**: `composer test` or `t`
- **Run single test**: `composer test -- 'output\.spec\.php$'` (regex filter, not a file path)
- **Static analysis**: `composer static` (Mago)
- **Linting**: `composer lint` (Mago; style/size suggestions are advisory)
- **Formatting check**: `composer code-check` (Mago, read-only)
- **Apply formatting**: `composer format`
- **All checks**: `composer check`

## Code Style
- **PHP**: >=8.3 with `declare(strict_types=1)`
- **Formatting**: PSR-12 (enforced by Mago)
- **Indentation**: 4 spaces, UTF-8, LF endings, single quotes
- **Types**: Strict typing, use `mixed`, `?Type`, union types
- **Properties**: Private by default, `readonly` when immutable
- **Methods**: Constructor property promotion, arrow functions (`fn`)
- **Imports**: Group by type, alphabetical within groups
- **Naming**: PascalCase classes, camelCase methods/properties
- **Error handling**: Exceptions over error codes
- **Forbidden**: `shell_exec` (Mago rule)

## Testing
- **Framework**: swew/test with `it()` and `expect()`
- **Files**: `*.spec.php`, `*.test.php`
- **Structure**: BDD style, descriptive test names
- **Assertions**: `expect($value)->toBe($expected)`

## Architecture
- **Namespace**: `Swew\Cli\` (PSR-4)
- **Commands**: Extend `Command` class with NAME/DESCRIPTION constants
- **Arguments**: Use `CommandArgument` for parsing
- **Output**: Via `$this->output` methods</content>
<parameter name="filePath">/Users/avil/git/pet-projects/swew/swew-cli/AGENTS.md