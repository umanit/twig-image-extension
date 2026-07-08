# UmanIT - Twig Image Extension

This Twig extension facilitates the integration of responsive images markup in Twig templates.

It uses [LiipImagineBundle](https://symfony.com/doc/2.0/bundles/LiipImagineBundle/index.html) and its filters to
generate HTML markup with all you need to handle responsive images.

Lazy loading relies on the browser-native [`loading="lazy"`](https://developer.mozilla.org/docs/Web/HTML/Element/img#loading)
attribute, so no JavaScript library is required. An optional blur-up effect (a small, dependency-free JavaScript module
and CSS file) is provided if you want it.

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

(Optional) Install bundle assets if you want to use the blur-up effect (JavaScript module and CSS file):

```bash
bin/console assets:install --symlink
```

## Configuration

```yaml
umanit_twig_image:
    allow_fallback: false
    use_liip_default_image: false
    lazy_load:
        blur: false
        blur_class_selector: lazy-blur
```

The `*_lazy_load` functions render images using the native `loading="lazy"` attribute.

Set `lazy_load.blur` to `true` to enable the optional blur-up effect: the placeholder (generated with the
`placeholderFilter` filter) is exposed as a `background-image` and the real image fades in once loaded. This requires
importing the provided [JavaScript module](#optional-blur-up-effect) and CSS file. The class added on the `img` can be
customized with `lazy_load.blur_class_selector`.

| ⚠ | If you customize `blur_class_selector`, you cannot use the provided JavaScript module and CSS that rely on the default `lazy-blur` class anymore |
|---|-------------------------------------------------------------------------------------------------------------------------------------------------|

### Fallback images

By default, if the image path given in functions calls is null, empty or points to a missing file on the server, an
exception is thrown.
You have two options to avoid this:

* setting `twig_image_extension.allow_fallback` to `true`
* setting `twig_image_extension.use_liip_default_image` to `true`

#### `twig_image_extension.allow_fallback`

If the path given points to a missing file, a default image will be rendered instead. The default images are available
in four sizes:

- small: 320px wide
- medium: 640 wide
- large: 1280px wide
- extra large: 2560px wide (for Retina screens mostly)

If a default image needs to be rendered, the size will be guessed using the given Liip filter:

- a filter ending with `2x` will give you an extra large default image
- a filter ending with `xl` or `xxl` will give you a large default image
- a filter ending with `xs` or `xxs` will give you a small default image
- any other filter name will default to the medium size

#### `twig_image_extension.use_liip_default_image`

This parameter will only be used as a backup if `allow_fallback` is set to `false` and requires you to use the default
image mecanism of Liip (
see [Liip configuration](https://symfony.com/bundles/LiipImagineBundle/current/configuration.html))

| ⚠ | If neither `twig_image_extension.allow_fallback` nor `twig_image_extension.use_liip_default_image`  are set to `true` and the image isn't found on the server, an exception will be thrown! |
|---|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|

## Usage

The following Twig functions are available in your templates.

1. [umanit_image_figure_lazy_load](#umanit_image_figure_lazy_load)
1. [umanit_image_figure](#umanit_image_figure)
1. [umanit_image_picture_lazy_load](#umanit_image_picture_lazy_load)
1. [umanit_image_picture](#umanit_image_picture)
1. [umanit_image_img](#umanit_image_img)
1. [umanit_image_srcset](#umanit_image_srcset)
1. [(Optional) Blur-up effect](#optional-blur-up-effect)

When a [LiipImagine filter](https://symfony.com/doc/2.0/bundles/LiipImagineBundle/filters.html#built-in-filters) is
used, the extension will read its configuration and automatically guess the right width or height to apply in the
markup. If it's not possible, the extension will try to get the original image dimensions instead. In both case, the
result is saved in cache to avoid multiple process for the same image.

When the used function is for lazy load, the `loading="lazy"` attribute is added on the `img`. If the `blur` option is
enabled, the `lazy-blur` class (customizable, see the [Configuration](#configuration) part) is added as well.

`width` and `height` attributes are added in the `<img />` tag, based on the size calculated by the `src` filter
(except for `downscale` and `upscale` filters, which fallback to the original image size). By doing this, sudden layout
shifts are avoided for a better user experience.

To use `htmlAlt`, the css file `umanit-alt-text.css` must be loaded. It hides the `div` used to display the html alt
content.

### umanit_image_figure_lazy_load

Generates a `figure` tag with a natively lazy loaded `img` inside (`loading="lazy"`). If the `blur` option is enabled,
the `lazy-blur` class and the placeholder `background-image` are added for the blur-up effect.

#### Parameters

| Name                 | Explanation                                                                                                                                |
|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| path                 | Path to the image, used to generated the browser path with LiipImagine                                                                     |
| srcFilter            | Name of the LiipImagine filter used to generate the path for `src`                                                                    |
| placeholderFilter    | Name of the LiipImagine filter used to generate the blur-up placeholder `background-image` (only when `blur` is enabled)                    |
| srcsetFilters        | A list of LiipImagine filters used to generate the `srcset`                                                                           |
| alt                  | The text to put in the `alt` attribute of the `img`                                                                                        |
| imgClass             | Classes to add on the `img`                                                                                                                |
| sizes                | Value of the `sizes` attribute (`100vw` if not defined)                                                                                    |
| figureClass          | Classes to add on the `figure`                                                                                                             |
| figcaptionText       | Text of the `figcaption` (if nothing is passed, no `figcaption` will be rendered)                                                          |
| figcaptionClass      | Classes to add on the `figcaption`                                                                                                         |
| imgImportance        | Importance of the image (see [this link](https://web.dev/priority-hints/) for more information)                                            |
| figureDataAttributes | Raw string passed to add `data-attributes` on the `figure`                                                                                 |
| imgDataAttributes    | Raw string passed to add `data-attributes` on the `img`                                                                                    |
| htmlAlt              | The html to put in a div referenced by the `aria-describedby` of the `img`. If given a non-empty value, the `alt` attribute vill be empty. |

#### Example

<details>
  <summary>Click to show</summary>

##### Without htmlAlt

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
        'Figcaption text',
        'class-figcaption',
        'high',
        'data-container="a"',
        'data-image="b" data-test'
      )
  ```

HTML generated:

  ```html

<figure class="class-figure" data-container="a">
  <img
      alt="image alt"
      class="lazy-blur img img--cover img--zoom"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      loading="lazy"
      style="background-image:url(https://domain.tld/media/cache/tiny_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg)"
      sizes="(min-width: 768px) 33.3vw, 100vw"
      width="600" height="400"
      importance="high"
      data-image="b" data-test
  >
  <figcaption class="class-figcaption">Figcaption text</figcaption>
</figure>
  ```

##### With htmlAlt

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
        'Figcaption text',
        'class-figcaption',
        '<p>Html to describe content</p>'
      )
  ```

HTML generated

  ```html

<figure class="class-figure">
  <img
      alt=""
      aria-describedby="1234567890"
      class="lazy-blur img img--cover img--zoom"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      loading="lazy"
      style="background-image:url(https://domain.tld/media/cache/tiny_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg)"
      sizes="(min-width: 768px) 33.3vw, 100vw"
      width="600" height="400"
  >
  <figcaption class="class-figcaption">Figcaption text</figcaption>
</figure>
<div id="1234567890"><p>Html to describe content</p></div>
  ```

The id used for `aria-describedby` is a random dynamically generated value.
</details>

### umanit_image_figure

Generates a `figure` tag with an `img` inside.

#### Parameters

| Name                 | Explanation                                                                                                                                |
|----------------------|--------------------------------------------------------------------------------------------------------------------------------------------|
| path                 | Path to the image, used to generated the browser path with LiipImagine                                                                     |
| srcFilter            | Name of the LiipImagine filter used to generate the path for `src`                                                                         |
| srcsetFilters        | A list of LiipImagine filters used to generate the `srcset`                                                                                |
| alt                  | The text to put in the `alt` attribute of the `img`                                                                                        |
| imgClass             | Classes to add on the `img`                                                                                                                |
| sizes                | Value of the `sizes` attribute (`100vw` if not defined)                                                                                    |
| figureClass          | Classes to add on the `figure`                                                                                                             |
| figcaptionText       | Text of the `figcaption` (if nothing is passed, no `figcaption` will be rendered                                                           |
| figcaptionClass      | Classes to add on the `figcaption`                                                                                                         |
| imgImportance        | Importance of the image (see [this link](https://web.dev/priority-hints/) for more information)                                            |
| figureDataAttributes | Raw string passed to add `data-attributes` on the `figure`                                                                                 |
| imgDataAttributes    | Raw string passed to add `data-attributes` on the `img`                                                                                    |
| htmlAlt              | The html to put in a div referenced by the `aria-describedby` of the `img`. If given a non-empty value, the `alt` attribute vill be empty. |

#### Example

<details>
  <summary>Click to show</summary>

##### Without htmlAlt

  ```twig
      umanit_image_figure(
        image.path,
        'small_thumbnail',
        ['thumbnail', 'large_thumbnail'],
        'image alt',
        'img img--cover img--zoom',
        '(min-width: 768px) 33.3vw, 100vw',
        'class-figure',
        'Figcaption text',
        'class-figcaption',
        'low',
        'data-container="a"',
        'data-image="b" data-test'
      )
  ```

HTML generated:

  ```html

<figure class="class-figure" data-container="a">
  <img
      alt="image alt"
      class="img img--cover img--zoom"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      sizes="(min-width: 768px) 33.3vw, 100vw"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      width="600" height="400"
      importance="low"
      importance="high"
      data-image="b" data-test
  >
  <figcaption class="class-figcaption">Figcaption text</figcaption>
</figure>
  ```

##### With htmlAlt

 ```twig
      umanit_image_figure(
        image.path,
        'small_thumbnail',
        ['thumbnail', 'large_thumbnail'],
        'image alt',
        'img img--cover img--zoom',
        '(min-width: 768px) 33.3vw, 100vw',
        'class-figure',
        'Figcaption text',
        'class-figcaption',
        '<p>Html to describe content</p>'
      )
  ```

HTML generated

  ```html

<figure class="class-figure">
  <img
      alt=""
      aria-describedby="1234567890"
      class="img img--cover img--zoom"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      sizes="(min-width: 768px) 33.3vw, 100vw"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      width="600" height="400"
  >
  <figcaption class="class-figcaption">Figcaption text</figcaption>
</figure>
<div id="1234567890"><p>Html to describe content</p></div>
  ```

The id used for `aria-describedby` is a random dynamically generated value.
</details>

### umanit_image_picture_lazy_load

Generates a `picture` tag with an `img` inside and X `source`. Each `source` can have a `media` and `sizes` attribute if
needed. The `img` uses the native `loading="lazy"` attribute. If the `blur` option is enabled, the `lazy-blur` class and
the placeholder `background-image` are added for the blur-up effect.

#### Parameters

| Name                  | Explanation                                                                                                                                                                                                                                                                                  |
|-----------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| path                  | Path to the image, used to generate the browser path with LiipImagine                                                                                                                                                                                                                        |
| srcFilter             | Name of the LiipImagine filter used to generate the path for `src`                                                                                                                                                                                                                      |
| placeholderFilter     | Name of the LiipImagine filter used to generate the blur-up placeholder `background-image` (only when `blur` is enabled)                                                                                                                                                                      |
| srcsetFilters         | A list of LiipImagine filters used to generate the `srcset`                                                                                                                                                                                                                             |
| sources               | A list of LiipImagine filters used to generate the `sources` tags. The key of the array is the path to the image and the value can be a list of filters name or, if you need to define a `media` or `sizes` attribute on the source, an array with `filters` and `media` and/or `sizes` key. |
| alt                   | The text to put in the `alt` attribute of the `img`                                                                                                                                                                                                                                          |
| imgClass              | Classes to add on the `img`                                                                                                                                                                                                                                                                  |
| pictureClass          | Classes to add on the `picture`                                                                                                                                                                                                                                                              |
| imgImportance         | Importance of the image (see [this link](https://web.dev/priority-hints/) for more information)                                                                                                                                                                                              |
| pictureDataAttributes | Raw string passed to add `data-attributes` on the `picture`                                                                                                                                                                                                                                  |
| imgDataAttributes     | Raw string passed to add `data-attributes` on the `img`                                                                                                                                                                                                                                      |
| htmlAlt               | The html to put in a div referenced by the `aria-describedby` of the `img`. If given a non-empty value, the `alt` attribute vill be empty.                                                                                                                                                   |

#### Example

<details>
  <summary>Click to show</summary>

##### Without htmlAlt

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
      'class-picture',
      'high',
      'data-picture data-markup="c"',
      'data-image="d"
    )
  ```

HTML generated

  ```html

<picture class="class-picture" data-picture data-markup="c">
  <source media="(min-width: 768px)" sizes="(min-width: 1400px) 25vw, 50vw" srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w" width="600" height="400">
  <source srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w" width="300" height="200">
  <img
      alt="alt img"
      class="lazy-blur img img-fluid"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      loading="lazy"
      style="background-image:url(https://domain.tld/media/cache/tiny_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg)"
      width="600" height="400"
      importance="high"
      data-image="d"
  >
</picture>
  ```

##### With htmlAlt

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
      'class-picture',
      '<p>Html to describe content</p>'
    )
  ```

HTML generated

  ```html

<picture class="class-picture">
  <source media="(min-width: 768px)" sizes="(min-width: 1400px) 25vw, 50vw" srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w" width="600" height="400">
  <source srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 2880w" width="300" height="200">
  <img
      alt=""
      aria-describedby="1234567890"
      class="lazy-blur img img-fluid"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      loading="lazy"
      style="background-image:url(https://domain.tld/media/cache/tiny_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg)"
      width="600" height="400"
  >
</picture>
<div id="1234567890"><p>Html to describe content</p></div>
  ```

The id used for `aria-describedby` is a random dynamically generated value.
</details>

### umanit_image_picture

Generates a `picture` tag with an `img` inside and X `source`. Each `source` can have a `media` and `sizes` attribute if
needed.

#### Parameters

| Name                  | Explanation                                                                                                                                                                                                                                                                                  |
|-----------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| path                  | Path to the image, used to generate the browser path with LiipImagine                                                                                                                                                                                                                        |
| srcFilter             | Name of the LiipImagine filter used to generate the path for `src`                                                                                                                                                                                                                           |
| srcsetFilters         | A list of LiipImagine filters used to generate the `srcset`                                                                                                                                                                                                                                  |
| sources               | A list of LiipImagine filters used to generate the `sources` tags. The key of the array is the path to the image and the value can be a list of filters name or, if you need to define a `media` or `sizes` attribute on the source, an array with `filters` and `media` and/or `sizes` key. | | alt | The text to put in the `alt` attribute of the `img` |
| imgClass              | Classes to add on the `img`                                                                                                                                                                                                                                                                  |
| pictureClass          | Classes to add on the `picture`                                                                                                                                                                                                                                                              |
| imgImportance         | Importance of the image (see [this link](https://web.dev/priority-hints/) for more information)                                                                                                                                                                                              |
| pictureDataAttributes | Raw string passed to add `data-attributes` on the `picture`                                                                                                                                                                                                                                  |
| imgDataAttributes     | Raw string passed to add `data-attributes` on the `img`                                                                                                                                                                                                                                      |
| htmlAlt               | The html to put in a div referenced by the `aria-describedby` of the `img`. If given a non-empty value, the `alt` attribute vill be empty.                                                                                                                                                   |

#### Example

<details>
  <summary>Click to show</summary>

##### Without htmlAlt

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
      'class-picture',
      'low',
      'data-picture data-markup="c"',
      'data-image="d"
    )
  ```

HTML generated

  ```html

<picture class="class-picture" data-picture data-markup="c">
  <source media="(min-width: 768px)" sizes="(min-width: 1400px) 25vw, 50vw" srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w" width="600" height="400">
  <source srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w" width="300" height="200">
  <img
      class="img img-fluid"
      alt="alt img"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      width="600" height="400"
      importance="low"
      data-image="d"
  >
</picture>
  ```

##### With htmlAlt

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
      'class-picture',
      '<p>Html to describe content</p>'
    )
  ```

HTML generated

  ```html

<picture class="class-picture">
  <source media="(min-width: 768px)" sizes="(min-width: 1400px) 25vw, 50vw" srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w" width="600" height="400">
  <source srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg2 2880w" width="300" height="200">
  <img
      class="img img-fluid"
      alt=""
      aria-describedby="1234567890"
      src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
      srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
      width="600" height="400"
  >
</picture>
<div id="1234567890"><p>Html to describe content</p></div>
  ```

</details>

### umanit_image_img

Generates an `img` tag.

#### Parameters

| Name              | Explanation                                                                                     |
|-------------------|-------------------------------------------------------------------------------------------------|
| path              | Path to the image, used to generate the browser path with LiipImagine                           |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `src`                              |
| srcsetFilters     | A list of LiipImagine filters used to generate the `srcset`                                     |
| alt               | The text to put in the `alt` attribute of the `img`                                             |
| imgClass          | Classes to add on the `img`                                                                     |
| sizes             | Value of the `sizes` attribute (`100vw` if not defined)                                         |
| importance        | Importance of the image (see [this link](https://web.dev/priority-hints/) for more information) |
| imgDataAttributes | Raw string passed to add `data-attributes` on the `img`                                         |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
      umanit_image_img(
        image.path,
        'small_thumbnail',
        ['thumbnail', 'large_thumbnail'],
        'image alt',
        'img img--cover img--zoom',
        '(min-width: 768px) 33.3vw, 100vw',
        'low',
        'data-image="b" data-test'
      )
  ```

HTML generated:

  ```html
  <img
    alt="image alt"
    class="img img--cover img--zoom"
    src="https://domain.tld/media/cache/resolve/small_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg"
    sizes="(min-width: 768px) 33.3vw, 100vw"
    srcset="https://domain.tld/media/cache/resolve/thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 260w, https://domain.tld/media/cache/resolve/large_thumbnail/99/30/c1f268bbf1487fb88734f2ba826b.jpeg 2880w"
    width="600" height="400"
    importance="low"
    data-image="b" data-test
>
  ```

</details>

### umanit_image_srcset

Generates the content of a `srcset` attribute if you wan to use it in your own markup.

#### Parameters

| Name    | Explanation                                                           |
|---------|-----------------------------------------------------------------------|
| path    | Path to the image, used to generate the browser path with LiipImagine |
| filters | A list of LiipImagine filters used to generate the `srcset`           |

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

### (Optional) Blur-up effect

Lazy loading works out of the box with the native `loading="lazy"` attribute, no JavaScript required.

If you enabled `lazy_load.blur` in the [Configuration](#configuration), import the provided (dependency-free) JavaScript
module and CSS file to power the blur-up effect. The module registers a single `load` listener that removes the blur
once the real image has loaded. An optional `loadEventCallback` argument is called for each loaded image.

```js
import umanitImageLazyLoad from '../../public/bundles/umanittwigimage/js/umanit-image-lazy-loading';
import '../../public/bundles/umanittwigimage/css/umanit-image-lazy-loading.css';

umanitImageLazyLoad();
```

Or import the CSS through Twig:

```twig
<link rel="stylesheet" href="{{ asset('css/umanit-image-lazy-loading.css', 'twig_image_bundle') }}">
```

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

[MIT](https://choosealicense.com/licenses/mit/)
