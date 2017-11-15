# Wasp: WordPress Automated Setup Program 🐝

Wasp is a tool to turn simple YAML configuration files into working WordPress code.
Using a variety of "handlers", wasp parses your configuration file and produces the necessary code
to register post types, taxonomies, menus, and much more! Wasp reduces the tedious nature of this
work by leveraging sensible defaults and accepting user-defined defaults for complete flexibility.

Wasp is best employed as part of your build process, which means only the YAML file(s) would be stored in your version control system.
In the course of a build process, wasp parses the configuration file and generates a PHP file that can act as
the `functions.php` file of a theme, the main plugin file of a plugin, the entirety of an mu-plugin, or a file that is included by any of the former.
However, wasp can also be used to generate code that you can use as a starting point for your project, allowing you to save and modify it from there.
As such, if at any point you want to ditch wasp, you'd simply need to generate your code, save it, and remove wasp from your project.
Wasp gives you the benefit of a theme or plugin framework without ever having to commit!

## Installing

The preferred way to install wasp is via [Composer](https://getcomposer.org/):

```sh
$ composer require oomphinc/wasp --dev
```

Wasp will now be available in composer's ["bin dir"](https://getcomposer.org/doc/articles/vendor-binaries.md), which is located in `vendor/bin` by default.
You should be able to run wasp via:

```sh
$ vendor/bin/wasp
```

Wasp doesn't come with any handlers out of the box so that you can leverage only the features you want and nothing more.
In order to get started, you'll need to install one or more wasp plugins that contain handlers.
All of your essential handlers for WordPress basics can be found in [oomphinc/wasp-core](https://github.com/oomphinc/wasp-core):

```sh
$ composer require oomphinc/wasp-core --dev
```

## Creating a Config YAML File

The config YAML file can be named anything you'd like and stored anywhere, too.
When you run the `generate` command, you will specify the location of your config file.
The only stipulation is that the file contain valid YAML and properly formatted data for each of your handlers.

The properties you set inside of your YAML file will depend upon the handlers you intend to use.
Typically, one top-level property will be used by a single handler, but sometimes multiple handlers
will act upon the same property for different purposes, or a handler may reference properties outside
of the main one it handles to obtain additional information.

All config files should contain an `about` property, which defines details about the file itself:

```yml
about:
  name: My First Wasp Project # a unique name used to differentiate this project from others
  dir: inc # sub dir of this file relative to the project's file root (if applicable)
  url_context: %home_url%/food # "theme" or "plugin" or custom URL (with replacements) that corresponds to the project's file root
```

## Generating Code

## Extending

