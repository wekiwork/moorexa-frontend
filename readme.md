# Build With Speed

> “If one day the speed kills me, do not cry because I was smiling.” 
> ―  Paul Walker

Moorexa is an expressive Open-Source PHP MVC Framework with an underlining nature for simplicity and flexibility. It produces a stable, secure system, easier to scale, maintain and document;

## The framework,
1. Defines an elegant Model View Controller (MVC) system
2. Encourages freedom, creativity, simplicity, and speed
3. Provides a rich Command-line-interface (CLI) for maximum productivity and comfort
4. Incorporates best practices from other frameworks with improvements and ideas of its own
5. Welcomes any skill level
6. Has a beautiful routing mechanism, multiple database supports, and a fast development cycle for APIs (Application programming interface)

## Here are some core features built for your comfort and speed;
1. Reusable partials with custom classes.
2. Reusable hyper HTML directives for PHP inspired by React JSX
3. Migration and schema builder for database tables
4. Event Management
5. Namespace, autoloaders, shortcuts and endless options for  dynamic building blocks
6. Classic middlewares, providers, authentication and exception handlers, storage management and much more
7. Built-in template engine for HTML called REXA
8. Caching mechanisms for views, partials, templates and more
9. Query builder for (mysql,sqlite,pgsql) database systems
10. Assets bundling and so much more

## Getting Started
You've obtained a copy of moorexa, just extract into a working directory, you can run this command from your terminal in the current working directory

```bash
    php assist init
```

This command would clean up the caching system, generate all the neccessary keys and salts, setup your session and cookie drivers, install the required composer packages. This is entirely optional.

If you want to use a database driver for session and cookie, setup a default database connection in 
'dist/database/database.php', you can setup a new database with this command;

```bash
    php assist database create 'database-name' -pass='your dbms password eg. root'
```

and this command to add a database configuration;

```bash
    php assist database add 'connection-name' -default=dev
```

and configure session name and cookie name in 'dist/config/config.php', change the driver to 'database' to make this switch.