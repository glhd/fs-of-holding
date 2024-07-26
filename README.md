# The filesystem of holding

## Installation

```shell
composer require glhd/fs-of-holding
```

Make sure you have one of the following set:

- `openai.api_key` config value
- `services.openai.api_key` config value
- `services.openai.key` config value
- `services.chatgpt.api_key` config value
- `services.chatgpt.key` config value
- `OPENAI_API_KEY` env variable

## Usage

```php
// Grab a haiku from the filesystem of holding
file_get_contents('fs-of-holding://haiku.txt');

// Or maybe a sample config file
file_get_contents('fs-of-holding://pest-php/sample-config.php');

// Or maybe you need inspiration
file_get_contents('fs-of-holding://quotes-from-laravel-inspire-command.yml');
```
