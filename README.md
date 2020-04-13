# UmanIT - Twig Image Extension

This Twig extension facilitate the integration of responsive images' markup in Twig templates.

It use [LiipImagineBundle](https://symfony.com/doc/2.0/bundles/LiipImagineBundle/index.html) and his filters to
generate HTML markup with all you need to handle responsive images.

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

## Usage

The following Twig functions are available in your templates.

1. [umanit_image_figure](#umanit_image_figure)
1. [umanit_image_picture](#umanit_image_picture)
1. [umanit_image_srcset](#umanit_image_srcset)

When a [LiipImagine filter](https://symfony.com/doc/2.0/bundles/LiipImagineBundle/filters.html#built-in-filters) is
used, the extension will read his configuration and automatically takes the right width to apply in the markup.

List of supported filters:

 * relative_resize: Use the `widen` value
 * thumbnail: Use the first value of `size`

### umanit_image_figure

Generates a `figure` tag with an `img` inside and his `noscript` version. The `lazy` and `lazy-placeholder` classes are
always add to facilitate the integration with [yall.js](https://github.com/malchata/yall.js/) for example.

#### Parameters

| **Name**          | **Explanation**                                                         |
|-------------------|-------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine  |
| placeholderFilter | Name of the LiipImagine filter used to generate the path for `src`      |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `data-src` |
| srcsetFilters     | A list of LiipImagine filters used to generate the `data-srcset`        |
| alt               | The text to put in the `alt` attribute of the `img`                     |
| class             | Classes to add on the `img`                                             |
| sizes             | Value of the `sizes` attribute (`100vw` if not defined)                 |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
      umanit_image_figure(
        image.path,
        'tiny_thumbnail',
        'small_thumbnail',
        ['thumbnail', 'large_thumbnail'],
        'image alt',
        'img img--cover img--zoom',
        '(min-width: 768px) 33.3vw, 100vw'
      )
  ```

  HTML generated

  ```html
  <figure>
    <img
      alt="image alt"
      class="lazy lazy-placeholder img img--cover img--zoom"
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

### umanit_image_picture

Generates a `picture` tag with an `img` inside and X `source`. Each `source` can have a `media` and `sizes` attribute
if needed.

#### Parameters

| **Name**          | **Explanation**                                                                                                                                                                                                                                                                              |
|-------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| path              | Path to the image, used to generated the browser path with LiipImagine                                                                                                                                                                                                                       |
| placeholderFilter | Name of the LiipImagine filter used to generate the path for `src`                                                                                                                                                                                                                           |
| srcFilter         | Name of the LiipImagine filter used to generate the path for `data-src`                                                                                                                                                                                                                      |
| srcsetFilters     | A list of LiipImagine filters used to generate the `data-srcset`                                                                                                                                                                                                                             |
| sources           | A list of LiipImagine filters used to generate the `sources` tags. The key of the array is the path to the image and the value can be a list of filters name or, if you need to define a `media` or `sizes` attribute on the source, an array with `filters` and `media` and/or `sizes` key. |
| alt               | The text to put in the `alt` attribute of the `img`                                                                                                                                                                                                                                          |
| class             | Classes to add on the `img`                                                                                                                                                                                                                                                                  |

#### Example

<details>
  <summary>Click to show</summary>

  ```twig
    umanit_image_picture(
      image.path,
      'tiny_thumbnail',
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
      'img img-fluid'
    )
  ```

  HTML generated

  ```html
  <picture>
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

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
[MIT](https://choosealicense.com/licenses/mit/)
