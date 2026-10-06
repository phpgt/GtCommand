Provides the `gt` command for automating WebEngine development.
===============================================================

This repository holds the CLI functionality for [PHP.Gt/WebEngine](https://www.php.gt/webengine), exposed via the `gt` command line.

The following commands are exposed:

+ `gt add` - add a page, API endpoint or cron script from a template
+ `gt create` - create a new WebEngine application
+ `gt init` - initialise WebEngine in the current directory
+ `gt test` - run configured PHP and JavaScript test suites
+ `gt serve` - run the inbuilt development server
+ `gt build` - compile client-side assets
+ `gt cron` - invoke scripts or static functions at regular intervals
+ `gt run` - run all background scripts at once - a combination of `serve`, `build --watch` and `cron --watch --now`
+ `gt deploy` - instantly deploy your application to the internet
+ `gt migrate` - apply optional SQL-file and ORM Entity migrations

## `gt migrate`

`gt migrate` supports two independent migration styles. Numbered SQL files in
`query/_migration` are applied when that directory contains migrations. When
`phpgt/orm` is installed, Entity classes in `app.class_dir` are also compared
with the latest schema recorded in the ORM's separate `_orm` table.

A project may use SQL migrations, ORM migrations, both, or neither. SQL files
run first when both styles are present. If they fail, ORM migration does not
run. Useful ORM options are:

+ `--no-orm` - skip Entity migrations
+ `--orm-plan` - show the Entity schema changes without applying them
+ `--orm-baseline` - record an existing matching schema without changing tables

## `gt add`

Use `gt add type name [template]` to create new files in the current project.

Supported types:

+ `page`
+ `api`
+ `cron`

Examples:

+ `gt add page about`
+ `gt add api users`
+ `gt add cron cleanup`
+ `gt add page about multi-column`

`type` and `name` are required. `template` is optional.

### Built-in templates

If no template name is provided, `gt add` copies the built-in templates from this package:

+ `src/Template/page/template.html` -> `page/<name>.html`
+ `src/Template/page/template.php` -> `page/<name>.php`
+ `src/Template/api/template.php` -> `api/<name>.php`
+ `src/Template/api/template.json` -> `api/<name>.json`
+ `src/Template/cron/template.php` -> `cron/<name>.php`

If a template file contains `{{name}}`, it is replaced with the provided `name`.

### Project templates

If a template name is provided, files are loaded from the current working directory instead:

+ `<type>/_template/<template>.*`

For example:

+ `gt add page about multi-column`

This will copy:

+ `page/_template/multi-column.html` -> `page/about.html`
+ `page/_template/multi-column.php` -> `page/about.php`

The same lookup rule applies for `api`, `cron`, and future types that may be added later.

# Proudly sponsored by

[JetBrains Open Source sponsorship program](https://www.jetbrains.com/community/opensource/)

[![JetBrains logo.](https://resources.jetbrains.com/storage/products/company/brand/logos/jetbrains.svg)](https://www.jetbrains.com/community/opensource/)
