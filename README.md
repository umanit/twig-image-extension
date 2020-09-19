# UmanIT - Twig Image Extension

This Twig extension facilitate the integration of responsive images' markup in Twig templates.

It use [LiipImagineBundle](https://symfony.com/doc/2.0/bundles/LiipImagineBundle/index.html) and his filters to
generate HTML markup with all you need to handle responsive images.

It also provide a javascript module to automatically instantiate [yall.js](https://github.com/malchata/yall.js/) on
rendered images.

## Installation

Use the package manager [composer](https://getcomposer.org/) to install the extension.

```bash
composer require umanit/twig-image-extension
```

Load the bundle into your Symfony project.

```php
<?php

# config/bundles.php
return [
    // ...
    Umanit\TwigImage\UmanitTwigImageBundle::class => ['all' => true],
];
```

(Optionnal) Install bundle assets if you want to use the javascript module for
[yall.js](https://github.com/malchata/yall.js/):

```bash
bin/console assets:install --symlink
```

## Configuration

Some functions render HTML markup with ability to use lazy loading on images. It's possible to customize the classes 
used.

You just need to create a file `config/packages/umanit_twig_image.yaml`:

```yaml
umanit_twig_image:
    class_selector: lazy
    placeholder_class_selector: lazy-placeholder
    blur_class_selector: lazy-blur
```

| ⚠ | If you customize classes, you can not use anymore the javascript module and CSS which relies on them |
|---|------------------------------------------------------------------------------------------------------|

## Usage

The following Twig functions are available in your templates.

1. [umanit_image_figure_lazy_load](#umanit_image_figure_lazy_load)
1. [umanit_image_figure](#umanit_image_figure)
1. [umanit_image_picture_lazy_load](#umanit_image_picture_lazy_load)
1. [umanit_image_picture](#umanit_image_picture)
1. [umanit_image_srcset](#umanit_image_srcset)
1. [(Optional) Javascript module to instantiate yall.js](#optional-javascript-module-to-instantiate-yalljs)
1. [(Optional) Import CSS files for blur effect on yall.js lazy images](#optional-import-css-files-for-blur-effect-on-yalljs-lazy-images)

When a [LiipImagine filter](https://symfony.com/doc/2.0/bundles/LiipImagineBundle/filters.html#built-in-filters) is
used, the extension will read his configuration and automatically takes the right width to apply in the markup.

When the used function is for lazy load, `lazy` and `lazy-placeholder` classes are used but can be customized as
explained in the [Configuration](#configuration) part.

List of supported filters:

 * [relative_resize](https://symfony.com/doc/current/bundles/LiipImagineBundle/filters/sizing.html#relative-resize):
 Use the `widen` value
 * [thumbnail](https://symfony.com/doc/current/bundles/LiipImagineBundle/filters/sizing.html#thumbnails): Use the first
 value of `size`

### umanit_image_figure_lazy_load

Generates a `figure` tag with an `img` inside and his `noscript` version. The `lazy`,
`lazy-placeholder` and `lazy-blur` classes are add to facilitate the integration with 
[yall.js](https://github.com/malchata/yall.js/) for example.

#### Parameters

| **Name**          | **Explanation**                                                         |
|-------------------|-------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine  |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `data-src` |
| placeholderFilter | Name of the LiipImagine filter used to generate the path for `src`      |
| srcsetFilters     | A list of LiipImagine filters used to generate the `data-srcset`        |
| alt               | The text to put in the `alt` attribute of the `img`                     |
| imgClass          | Classes to add on the `img`                                             |
| sizes             | Value of the `sizes` attribute (`100vw` if not defined)                 |
| figureClass       | Classes to add on the `figure`                                          |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
      umanit_image_figure_lazy_load(
        image.path,
        'small_thumbnail',
        'tiny_thumbnail',
        ['thumbnail', 'large_thumbnail'],
        'image alt',
        'img img--cover img--zoom',
        '(min-width: 768px) 33.3vw, 100vw',
        'class-figure',
      )
  ```

  HTML generated

  ```html
  <figure class="class-figure">
    <img
      alt="image alt"
      class="lazy lazy-placeholder lazy-blur img img--cover img--zoom"
      src="https://domain.tld/media/cache/tiny_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      data-src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      sizes="(min-width: 768px) 33.3vw, 100vw"
      data-srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
    >
    <noscript>
      <img
        class="img img--cover img--zoom"
        alt="home"
        src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
        sizes="(min-width: 768px) 33.3vw, 100vw"
        srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      >
    </noscript>
  </figure>
  ```
</details>

### umanit_image_figure

Generates a `figure` tag with an `img` inside.

#### Parameters

| **Name**          | **Explanation**                                                        |
|-------------------|------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `src`     |
| srcsetFilters     | A list of LiipImagine filters used to generate the `srcset`            |
| alt               | The text to put in the `alt` attribute of the `img`                    |
| imgClass          | Classes to add on the `img`                                            |
| sizes             | Value of the `sizes` attribute (`100vw` if not defined)                |
| figureClass       | Classes to add on the `figure`                                         |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
      umanit_image_figure(
        image.path,
        'small_thumbnail',
        ['thumbnail', 'large_thumbnail'],
        'image alt',
        'img img--cover img--zoom',
        '(min-width: 768px) 33.3vw, 100vw',
        'class-figure',
      )
  ```

  HTML generated

  ```html
  <figure class="class-figure">
    <img
      alt="image alt"
      class="lazy lazy-placeholder lazy-blur img img--cover img--zoom"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      sizes="(min-width: 768px) 33.3vw, 100vw"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
    >
  </figure>
  ```
</details>

### umanit_image_picture_lazy_load

Generates a `picture` tag with an `img` inside and X `source`. Each `source` can have a `media` and `sizes` attribute
if needed.  The `lazy` and `lazy-placeholder` classes are add to facilitate the  integration with
[yall.js](https://github.com/malchata/yall.js/) for example.

#### Parameters

| **Name**          | **Explanation**                                                                                                                                                                                                                                                                              |
|-------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine                                                                                                                                                                                                                       |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `data-src`                                                                                                                                                                                                                      |
| placeholderFilter | Name of the LiipImagine filter used to generate the path for `src`                                                                                                                                                                                                                           |
| srcsetFilters     | A list of LiipImagine filters used to generate the `data-srcset`                                                                                                                                                                                                                             |
| sources           | A list of LiipImagine filters used to generate the `sources` tags. The key of the array is the path to the image and the value can be a list of filters name or, if you need to define a `media` or `sizes` attribute on the source, an array with `filters` and `media` and/or `sizes` key. |
| alt               | The text to put in the `alt` attribute of the `img`                                                                                                                                                                                                                                          |
| imgClass          | Classes to add on the `img`                                                                                                                                                                                                                                                                  |
| pictureClass      | Classes to add on the `picture`                                                                                                                                                                                                                                                                  |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
    umanit_image_picture_lazy_load(
      image.path,
      'small_thumbnail',
      'tiny_thumbnail',
      ['thumbnail', 'large_thumbnail'],
      {
        (image.path): {
          'media': '(min-width: 768px)',
          'sizes': '(min-width: 1400px) 25vw, 50vw',
          'filters': ['thumbnail', 'large_thumbnail']
        },
        (image2.path): ['thumbnail', 'large_thumbnail']
      },
      'alt img',
      'img img-fluid',
      'class-picture'
    )
  ```

  HTML generated

  ```html
  <picture class="class-picture">
    <source media="(min-width: 768px)" sizes="(min-width: 1400px) 25vw, 50vw" srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w">
    <source srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 2880w">
    <img
      class="img img-fluid"
      alt="alt img"
      src="https://domain.tld/media/cache/tiny_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      data-src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      data-srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
    >
  </picture>
  ```
</details>


### umanit_image_picture

Generates a `picture` tag with an `img` inside and X `source`. Each `source` can have a `media` and `sizes` attribute
if needed.

#### Parameters

| **Name**          | **Explanation**                                                                                                                                                                                                                                                                              |
|-------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine                                                                                                                                                                                                                       |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `src`                                                                                                                                                                                                                           |
| srcsetFilters     | A list of LiipImagine filters used to generate the `srcset`                                                                                                                                                                                                                                  |
| sources           | A list of LiipImagine filters used to generate the `sources` tags. The key of the array is the path to the image and the value can be a list of filters name or, if you need to define a `media` or `sizes` attribute on the source, an array with `filters` and `media` and/or `sizes` key. |
| alt               | The text to put in the `alt` attribute of the `img`                                                                                                                                                                                                                                          |
| imgClass          | Classes to add on the `img`                                                                                                                                                                                                                                                                  |
| pictureClass      | Classes to add on the `picture`                                                                                                                                                                                                                                                                  |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
    umanit_image_picture(
      image.path,
      'small_thumbnail',
      ['thumbnail', 'large_thumbnail'],
      {
        (image.path): {
          'media': '(min-width: 768px)',
          'sizes': '(min-width: 1400px) 25vw, 50vw',
          'filters': ['thumbnail', 'large_thumbnail']
        },
        (image2.path): ['thumbnail', 'large_thumbnail']
      },
      'alt img',
      'img img-fluid',
      'class-picture'
    )
  ```

  HTML generated

  ```html
  <picture class="class-picture">
    <source media="(min-width: 768px)" sizes="(min-width: 1400px) 25vw, 50vw" srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w">
    <source srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 2880w">
    <img
      class="img img-fluid"
      alt="alt img"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
    >
  </picture>
  ```
</details>

### umanit_image_srcset

Generates the content of a `srcset` attribute if you wan to use it in your own markup.

#### Parameters

| **Name**          | **Explanation**                                                         |
|-------------------|-------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine  |
| filters           | A list of LiipImagine filters used to generate the `srcset`             |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
  umanit_image_srcset(image.path, ['thumbnail', 'large_thumbnail'])
  ```

  HTML generated

  ```html
  https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w
  ```
</details>

### (Optional) Javascript module to instantiate yall.js

If you want to use [yall.js](https://github.com/malchata/yall.js/) to manage the lazy load of your images, the bundle
provides a javascript module that can be called in your application.

[yall.js](https://github.com/malchata/yall.js/) needs to be installed manually: `yarn add yall-js`

Then you need to import the module and instantiate it by passing the yall library. An optional argument
`loadEventCallback` is available if you want to add more customization. It will be called in the `load` event of
[yall.js](https://github.com/malchata/yall.js/).

```js
import yall from 'yall-js';
import umanitImageLazyLoad from '../../public/bundles/umanittwigimage/js/umanit-image-lazy-loading';

umanitImageLazyLoad(yall);

```

### (Optional) Import CSS files for blur effect on yall.js lazy images

You can import the CSS file for adding a blur effect on lazy images.

```twig
<link rel="stylesheet" href="{{ asset('bundles/umanittwigimage/css/umanit-image-lazy-loading.css') }}">
```

Example in webpack
```js
import '../../public/bundles/umanittwigimage/css/umanit-image-lazy-loading.css';
```

⚠ For a best usage for the users without javascript you should add a `no-js` class on the `html` element

```html
<html class="no-js">
```

Finally, add this one line `<script>` before any `<link>` or `<style>` elements in the document `<head>`

```html
<!-- Remove the no-js class on the <html> element if JavaScript is on -->
<script>document.documentElement.classList.remove("no-js");</script>
```

See [https://github.com/malchata/yall.js/#what-about-users-without-javascript](https://github.com/malchata/yall.js/#what-about-users-without-javascript)
for more details.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](https://choosealicense.com/licenses/mit/)
