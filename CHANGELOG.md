# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres
to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Reverted

- Revert usage of `liip_imagine.twig.assets_version`

## [1.3.2] - 2025-03-10

### Fixed

- Parameter `$liipTwigAssetsVersion` of `umanit_twig_image.runtime` can be `null`.

## [1.3.1] - 2025-03-10

### Fixed

- Handle usage of `liip_imagine.twig.assets_version` configuration

## [1.3.0] - 2024-03-22

### Added

- New `twig_image_extension.allow_fallback` configuration setting (defaults to `false`), to allow for fallback images to
  be used in any environment

### Changed

- Catched `NotLoadableException` will trigger fallback image resolving if `twig_image_extension.allow_fallback` is set
  to `true`

## [1.2.1] - 2023-04-17

### Fixed

- Broken code in `getImagePicture`

## [1.2.0] - 2023-03-15

### Added

- New Twig function `umanit_image_img`, to generate an `<img>` tag markup
- In dev environments, leverages the data manager from Liip and its configured loader(s) to check if the image file
  exists.
  If it doesn't, renders a default image in one of four sizes (resolved by analyzing the filter name)
- Adds a `FallbackImageResolver` to resolve which default image to render
- Adds an asset package definition (`twig_image_bundle`)

### Fixed

- In dev environments, catches `NotLoadableException` from Liip and uses a default image instead to build the tag

## [1.1.1] - 2022-02-15

### Added

- Add an `imgImportance` option in functions in order to [optimize resource loading](https://web.dev/priority-hints/)
- Add an `imgDataAttributes` option in functions to pass custom `data-attributes` on the `<img />`
- Add an `pictureDataAttributes` (or `figureDataAttributes`) option in functions to pass custom `data-attributes` on
  the `<picture>` (or `<figure>`)
- Add `use_liip_default_image` in order to use the Liip default image if none are given in functions calls

### Fixed

- Fix slowness introduced by the 1.0.6 with calculation of filtered images sizes

## [1.1.0] - 2021-12-11

### Changed

- Drop support for PHP <7.4

### Fixed

- Self-close `<img />` elements

## [1.0.8] - 2021-08-02

### Added

- Add PHP 8.0 compatibility

## [1.0.7] - 2021-04-02

### Added

- Add the possibility to define a `figcaption` on `figure` markups

### Modified

- Add original image `width` and `height` attributes on `img` and `source` markups if not available for the given
  `filter`

## [1.0.6] - 2021-01-25

### Added

- Add `width` and `height` attributes on `img` markup if available for the given `filter`

## [1.0.5] - 2020-10-01

### Added

- Add an option to place a class on `figure` markups

### Fixed

- Fix incoherence in the `README`

## [1.0.4] - 2020-05-06

### Modified

- Decrease required PHP version to 7.2

## [1.0.3] - 2020-04-30

### Added

- Add a `loadEventCallback` parameter on `umanitImageLazyLoad` used in the `load` event of yall.js

## [1.0.2] - 2020-04-29

### Added

- Add a CSS file to make a blur effect on lazy load images
- Add a new configuration `umanit_twig_image.lazy_load.blur_class_selector` to specify the class name used to make the
  blur effect

## [1.0.1] - 2020-04-27

### Added

- Add a `figureClass` parameter on `umanit_image_figure_lazy_load` and `umanit_image_figure`

### Fixed

- Add `['is_safe' => ['html']]` on functions
- Use `data-srcset` on lazy load functions

### BC Break

- Rename `class` parameter to `imgClass` on all functions

## 1.0.0 - 2020-04-14

First release 🎉

[Unreleased]: https://github.com/umanit/twig-image-extension/compare/1.3.2...main

[1.3.2]: https://github.com/umanit/twig-image-extension/compare/1.3.1...1.3.2

[1.3.1]: https://github.com/umanit/twig-image-extension/compare/1.3.0...1.3.1

[1.3.0]: https://github.com/umanit/twig-image-extension/compare/1.2.1...1.3.0

[1.2.1]: https://github.com/umanit/twig-image-extension/compare/1.2.0...1.2.1

[1.2.0]: https://github.com/umanit/twig-image-extension/compare/1.1.1...1.2.0

[1.1.1]: https://github.com/umanit/twig-image-extension/compare/1.1.0...1.1.1

[1.1.0]: https://github.com/umanit/twig-image-extension/compare/1.0.8...1.1.0

[1.0.8]: https://github.com/umanit/twig-image-extension/compare/1.0.7...1.0.8

[1.0.7]: https://github.com/umanit/twig-image-extension/compare/1.0.6...1.0.7

[1.0.6]: https://github.com/umanit/twig-image-extension/compare/1.0.5...1.0.6

[1.0.5]: https://github.com/umanit/twig-image-extension/compare/1.0.4...1.0.5

[1.0.4]: https://github.com/umanit/twig-image-extension/compare/1.0.3...1.0.4

[1.0.3]: https://github.com/umanit/twig-image-extension/compare/1.0.2...1.0.3

[1.0.2]: https://github.com/umanit/twig-image-extension/compare/1.0.1...1.0.2

[1.0.1]: https://github.com/umanit/twig-image-extension/compare/1.0.0...1.0.1
