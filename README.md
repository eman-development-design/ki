# ki

## Getting Started

create a new project like so:

```shell
composer create-project eman-development-design/ki <destination-folder>
```

You can now design your template files.

## Page Tags

All template tags are manged from the file named `pageParams.json`.

The `global` tag is used to define global tags that are available on all pages.

The `pages` tag is used to define tags that are available on specific pages.

Minus `global` & `pages`, whatever you put in those sections is fully up to you.

**NOTE:** look at the `pageParams.json` file for examples.

## Compiling Pages

To compile your pages, run the following command:

```shell
bin/command ki:build
```

## TODOs

- Create sitemap.xml file
