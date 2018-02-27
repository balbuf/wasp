# Wasp: WordPress Automated Setup Program 🐝

Wasp is a tool for turning simple YAML configuration files into working WordPress code.
Using a variety of "handlers", wasp parses your configuration file and produces the necessary code
to register post types, taxonomies, menus, and much more! Wasp reduces the tedious nature of this
work by leveraging sensible defaults and accepting user-defined defaults for complete flexibility.

Wasp is best employed as part of your build process, which means only the YAML file(s) would be stored in
your version control system. In the course of a build process, wasp parses the configuration file and
generates a PHP file that can act as the `functions.php` file of a theme, the main plugin file of a plugin,
the entirety of an mu-plugin, or a file that is included by any of the former. However, wasp can also be used
to generate code that you can use as a starting point for your project, allowing you to save and modify it from
there. As such, if at any point you want to ditch wasp, you'd simply need to generate your code, save it, and
remove wasp from your project. Wasp gives you the benefit of a theme or plugin framework without ever having
to commit!

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

Wasp doesn't come with any handlers out of the box, so that you can leverage only the features you want and nothing more.
In order to get started, you'll need to install one or more wasp plugins that contain handlers.
All of the essential handlers for basic WordPress configuration can be found in [oomphinc/wasp-core](https://github.com/oomphinc/wasp-core):

```sh
$ composer require oomphinc/wasp-core --dev
```

## Creating a Config YAML File

The config YAML file can be named anything you'd like and stored anywhere, too.
When you run the `generate` command, you will specify the location of your config file.
The only stipulation is that the file contain valid YAML and data that is properly formatted for each of your handlers.

The properties you set inside of your YAML file will depend upon the handlers you intend to use.
Typically, one top-level property will be used by a single handler, but sometimes multiple handlers
will act upon the same property for different purposes, or a handler may reference properties outside
of the main one it handles to obtain additional information.

All config files should contain an `wasp` property, which defines details about the file itself, e.g.:

```yml
wasp:
  # a unique prefix used to differentiate this project from others
  prefix: wasp_example

  # location of this file relative to the file root of the project
  dir: inc

  # how wasp should determine the public URL that corresponds to the file root of the project
  url_context: plugin
```

Additional properties will depend on the handlers you wish to use, and you should consult the documentation
for the handlers' respective wasp plugin(s).

## Generating Code

Generating code requires an input file path (to your YAML config file) and an output file path (to the generated PHP code), e.g.:

```sh
$ vendor/bin/wasp generate config.yml themes/my-theme/functions.php
```

The input file path and/or output file path can be replaced by `-` to read data from `STDIN` or write data
to `STDOUT`, respectively.

Some handlers that work with additional files in your project (e.g. handlers that help with including files,
enqueing front-end assets, etc.) will need to know the path to your project's file root. The file root,
for example, would be the directory that contains your `functions.php` file for a theme or the main
plugin file for a plugin. Knowing the location of the file root on the filesystem and the relative location of your
generated file within (specified via the `wasp.dir` property of your config file) allows handlers to analyze files
on the filesystem and properly reference them from the generated PHP file.

In most cases, the file root can be discerned based on the output file path provided via the command line, in
combination with the `wasp.dir` property specified in the config file. However, in the case of writing to
`STDOUT`, explicitly specifying the project's file root may be required. For instance, the example above
could be rewritten as:

```sh
$ vendor/bin/wasp generate config.yml - --root=themes/my-theme > themes/my-theme/functions.php
```

If not specified or discernable from the output path, the file root defaults to the current working directory.

## Adding Handlers

Handlers are typically added to your project by installing additional wasp plugins via composer. When wasp runs,
it analyzes your project's `composer.lock` file to identify and execute all wasp plugins.

Considering all plugins/handlers installed in your project are activated by default, at times you may wish
to disable certain plugins and/or handlers when generating your code.

- To disable a plugin, use the `--disable-plugin=` option along with the composer package name for the plugin
- To disable a handler, use the `--skip-handler=` option along with the handler's machine name

Either option may be repeated to disable multiple plugins and/or handlers.

## Wasp Config Properties

The `wasp` top-level property contains information about the project and controls how code is generated.

| Property | Description |
| --- | --- |
| `prefix` | The prefix is required and should be unique for the particular component you are generating, which helps to avoid collisions with other components (e.g. if a WordPress site was using multiple plugins that were built with wasp). |
| `dir` | This specifies where the generated file is located on the filesystem relative to the file root. For instance, if the generated file was located at `themes/my-theme/inc/config.php` for the theme located in `themes/my-theme`, the value for `dir` should be `inc`. The default value is the file root itself. |
| `url_context` | The URL context helps wasp determine the public URL that corresponds to the file root, which is necessary for things like enqueing frontend assets. If you are creating a plugin or a theme and the file root corresponds to the plugin/theme's top-level directory, you can use `plugin` or `theme`, respectively. For custom setups, the URL context can be a URL pattern with a replacement token for one of the WordPress URL functions (e.g. using `home_url()` like `%home_url%/wp-content/themes/my-theme/`) or a hard-coded URL that points to the file root (e.g. `https://example.com/wp-content/themes/my-theme/`). The URL replacement tokens all correspond to the WordPress function of the same name and include: `%home_url%`, `%site_url%`, `%admin_url%`, `%includes_url%`, `%content_url%`, `%plugins_url%`, `%theme_url%`, `%get_stylesheet_directory_uri%`, and `%get_template_directory_uri%` . |
| `vars` | This is a convenient place to set variables that can be used throughout the config file (see Advanced Config below). |

## Advanced Config

Wasp uses [twig](https://twig.symfony.com/doc/1.x/) to process its config values, allowing the user to reference
other properties within the config and perform some basic value processing via twig's filters, functions, etc.
In order to use twig in your config property values, you must enclose some or all of the value in twig template
delimeters: `{{ }}` for expressions and `{% %}` for more complex control structures (loops, logic, etc.).
(Note that when a property value starts with a twig template, the value must be quoted, otherwise the YAML
parser will get confused and interprety the value as an inline object, e.g. `property: '{{ template }}'` instead
of the invalid `property: {{ template }}`.)

### Special Variables

Within a config property template, there are a number of special variables which allow access to surrounding property
values, the current property chain, etc.

| Variable | Description |
| --- | --- |
| `this` | a reference to the property's direct parent config object to easily access the value of its sibling properties, e.g. `this.sibling_prop`; to ascend further up the property chain, simply append `.parent` as necessary, e.g. `this.parent.parent.property` |
| `top` | a reference to the top-level config object, allowing access to any property in the config file |
| `vars` | a shortcut reference to `top.wasp.vars` which provides a convenient way to reuse config-wide variables |
| `prop` | an array of the current property chain, with the current property being index 0 and working backwards |
| `env` | a simple object with a `set()` method (which sets public properties that can then be accessed directly) that is shared amongst a property value template and any referenced property value templates, allowing communication with each other during value resolution; for example, calling `env.set('foo', 'bar')` allows accessing the value `bar` via `env.foo` |

Because twig is meant to be a templating engine which ultimately produces strings, special handling is necessary to
produce a non-string value for a property. An additional variable called `output` is provided which has a `setValue()`
method to override the resolved value of the property template, e.g. `{% do output.setValue(["this", "produces", "an", "array"]) %}`.
Similarly, the special `this`, `top`, and `vars` variables return strings for other property values by default.
To access a property value of another type, the property must be appended with `.getValue()`, e.g. `this.sibling_prop.getValue()`;
as a shortcut, the property name can be appended with `()` to access the raw value, e.g. `this.sibling_prop()`.

### User Defaults

For many handlers whose property structure is an associative array of associative arrays, wasp provides a
convenient way to provide default values to fill in those second-level associative arrays. To illustrate
this type of property structure:

```yml
handler_property:

  thing1:
    name: Thing 1
    color: red

  thing2:
    name: Thing 2
```

Notice how the handler's property is an associative array where the key (e.g. `thing1`) represents a slug
(machine friendly identifier) for the item and the value of the item is another associative array containing
the item's properties and values (e.g. `name`, `color`, etc.). When the item arrays have many possible properties,
it's not uncommon to find yourself repeating the same value again and again for each item.

In order to reduce repetition, you can add a `default` item whose properties are filled in for all the other items
when not explicitly set, e.g.:

```yml
handler_property:

  default:
    color: blue
    name: Untitled

  thing1:
    name: Thing 1
    color: red

  thing2:
    name: Thing 2
```

Since `thing2` did not specify a `color` property, it inherits the `color` value from the `default` item (blue).

#### Self-referential Property Templates

Another feature of the default item is the ability to reference the same property in the main items when the items'
property values are resolved. Instead of just providing default values, the default item can also _alter_ the
values of the properties in each main item. To make a self-referential template, the `this` keyword can be used.
In the following example, we use the default `post_type` property to add a prefix to the post type slug:

```yml
post_types:

  default:
    post_type: wasp_{{ this }}

  event:
    post_type: event

  testimonial:
    post_type: testimonial
```

When this config is resolved, the `post_type` property for our items will be `wasp_event` and `wasp_testimonial`,
respectively.

### Recipes

The advanced config using twig can be a little complicated, so here are some example "recipes" to help illustrate
the potential and allow you to get the most out of your config file with the least amount of work.

#### Conditional Prefix

The prefixed-post-type example above is great, but there are instances where you might need to use the `post_type`
wasp handler to modify a built-in post type or one provided by a plugin. In these instances, adding a prefix
to the post type value would be incorrect. We can make use of the `env` object to turn off the prefix for
certain items, e.g.:

```yml
post_types:

  default:
    post_type: '{% if not env.noPrefix %}wasp_{{ this }}{% endif %}'

  event:
    post_type: event

  testimonial:
    post_type: testimonial

  post:
    post_type: post{% do env.set('noPrefix', true) %}
```

Now the resolved `post_type` properties will be `wasp_event`, `wasp_testimonial`, and `post`.

This could also be achieved by adding a custom property to the item:

```yml
post_types:

  default:
    post_type: '{% if not this.no_prefix %}wasp_{{ this }}{% endif %}'

  event:
    post_type: event

  testimonial:
    post_type: testimonial

  post:
    post_type: post
    no_prefix: true
```

This can make the config look a little cleaner, but the possible disadvantage is that the `no_prefix` value
will be passed on to any handlers. In most cases the handler will ignore any properties it isn't expecting,
but the custom property could cause unintended consequences with some handlers.

#### Suffix with Default Value

Suppose you want to leverage a self-referential template while still providing a default value in case
the property was not set in one of the main items. This is possible, too! Take this imaginary `images`
handler, where we want to have a `.jpeg` file extension automatically added to the `file` property value,
while still providing a fallback file value:

```yml
images:

  default:
    file: '{{ ( this ?: "placeholder" ) ~ ".jpeg" }}'

  pic1:
    file: kittens
    alt: Kittens playing!

  pic2:
    file: puppies
    alt: Puppies playing!

  pic3:
    alt: Image coming soon!
```

When the config is resolved, the values of the file property will be `kittens.jpeg`, `puppies.jpeg`, and `placeholder.jpeg`.

#### Default Array Values

When specifying script dependencies, it's not uncommon for many or all to require `jquery`. We can set
this script (and any others) as the default base dependencies with the use of a `default` object and a
custom property. The `scripts` handler will look for the `dependencies` property, so we will leverage
that property in the `default` item and a custom property (`additional_dependencies`) to merge our base
script dependencies with any additional dependencies for each item:

```yml
scripts:

  default:
    dependencies: '{% set deps = ["jquery"] %}{% do output.setValue(this ?: (this.additional_dependencies() is iterable ? this.additional_dependencies() | merge(deps) : deps)) %}'

  main:
    additional_dependencies:
      - jquery-ui

  navigation:
    dependencies:
      - underscore
```

For the `main` item, it declares additional dependencies of `jquery-ui`, causing it to use the default base
dependencies and producing `['jquery', 'jquery-ui']`. For the `navigation` item, we don't need `jquery`, so
we use the `dependencies` property directly; the logic in our default `dependencies` property respects this
and produces `['underscore']` for this item.

## Extending

TODO: creating plugins, creating handlers, creating compilables, using compilables, including files, etc.
