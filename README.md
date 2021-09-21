Provides the `gt` command for automating WebEngine development.
===============================================================

This repository holds the CLI functionality for [PHP.Gt/WebEngine](https://www.php.gt/webengine), exposed via the `gt` command line.

The following commands are exposed:

+ `gt create` - create a new WebEngine application
+ `gt serve` - run the inbuilt development server
+ `gt build` - compile client-side assets
+ `gt cron` - invoke scripts or static functions at regular intervals
+ `gt run` - run all background scripts at once - a combination of `serve`, `build --watch` and `cron --watch --now`
+ `gt deploy` - instantly deploy your application to the internet
