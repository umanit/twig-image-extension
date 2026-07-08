<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Binary\FileBinaryInterface;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class Runtime
{
    private array $filters;
    private bool $lazyBlur;
    private string $lazyBlurClassSelector;

    public function __construct(
        private CacheInterface $cache,
        private CacheManager $cacheManager,
        FilterManager $filterManager,
        private DataManager $dataManager,
        private ImagineInterface $imagine,
        private bool $useLiipDefaultImage,
        private ?string $liipDefaultImage,
        private FallbackImageResolver $fallbackImageResolver,
        private bool $allowFallback,
    ) {
        $this->filters = $filterManager->getFilterConfiguration()->all();
    }

    public function setLazyLoadConfiguration(
        bool $blur,
        string $blurClassSelector,
    ): void {
        $this->lazyBlur = $blur;
        $this->lazyBlurClassSelector = $blurClassSelector;
    }

    public function getImageFigure(
        ?string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = '100vw',
        string $figureClass = '',
        string $figcaptionText = '',
        string $figcaptionClass = '',
        ?string $imgImportance = null,
        ?string $figureDataAttributes = null,
        ?string $imgDataAttributes = null,
        string $htmlAlt = '',
    ): string {
        $path = $this->processPath($path, $srcFilter);
        $id = str_replace('.', '', uniqid('', true));

        $nonLazyLoadImgMarkup = $this->getImageImg(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes,
            $imgImportance,
            $imgDataAttributes,
            $htmlAlt,
            $id,
        );

        $classFigureHtml = '' !== $figureClass ? \sprintf('class="%s"', $figureClass) : '';
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        $html = <<<HTML
        <figure $classFigureHtml $figureDataAttributes>
          $nonLazyLoadImgMarkup
          $figcaptionHtml
        </figure>
        HTML;

        if (!empty($htmlAlt)) {
            $html .= <<<HTML
        <div class="alt-visually-hidden" id="$id">$htmlAlt</div>
        HTML;
        }

        return $html;
    }

    public function getImageFigureLazyLoad(
        ?string $path,
        string $srcFilter,
        ?string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = '100vw',
        string $figureClass = '',
        string $figcaptionText = '',
        string $figcaptionClass = '',
        ?string $imgImportance = null,
        ?string $figureDataAttributes = null,
        ?string $imgDataAttributes = null,
        string $htmlAlt = '',
    ): string {
        $path = $this->processPath($path, $srcFilter);
        $id = str_replace('.', '', uniqid('', true));

        $imgMarkup = $this->getImageImgLazyLoad(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes,
            $imgImportance,
            $imgDataAttributes,
            $htmlAlt,
            $id,
        );
        $classFigureHtml = '' !== $figureClass ? \sprintf('class="%s"', $figureClass) : '';
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        $html = <<<HTML
        <figure $classFigureHtml $figureDataAttributes>
          $imgMarkup
          $figcaptionHtml
        </figure>
        HTML;

        if (!empty($htmlAlt)) {
            $html .= <<<HTML
        <div class="alt-visually-hidden" id="$id">$htmlAlt</div>
        HTML;
        }

        return $html;
    }

    public function getImagePicture(
        ?string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = '',
        ?string $imgImportance = null,
        ?string $pictureDataAttributes = null,
        ?string $imgDataAttributes = null,
        string $htmlAlt = '',
    ): string {
        $path = $this->processPath($path, $srcFilter);
        $id = str_replace('.', '', uniqid('', true));

        $sourcesMarkup = $this->getSourcesMarkup($sources);
        $imgMarkup = $this->getImageImg(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            null,
            $imgImportance,
            $imgDataAttributes,
            $htmlAlt,
            $id,
        );
        $classPictureHtml = '' !== $pictureClass ? \sprintf('class="%s"', $pictureClass) : '';

        $html = <<<HTML
        <picture $classPictureHtml $pictureDataAttributes>
          $sourcesMarkup
          $imgMarkup
        </picture>
        HTML;

        if (!empty($htmlAlt)) {
            $html .= <<<HTML
            <div class="alt-visually-hidden" id="$id">$htmlAlt</div>
            HTML;
        }

        return $html;
    }

    public function getImagePictureLazyLoad(
        ?string $path,
        string $srcFilter,
        ?string $placeholderFilter = null,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = '',
        ?string $importance = null,
        ?string $pictureDataAttributes = null,
        ?string $imgDataAttributes = null,
        string $htmlAlt = '',
    ): string {
        $path = $this->processPath($path, $srcFilter);
        $id = str_replace('.', '', uniqid('', true));

        $sourcesMarkup = $this->getSourcesMarkup($sources);
        $imgMarkup = $this->getImageImgLazyLoad(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            null,
            $importance,
            $imgDataAttributes,
        );
        $classPictureHtml = '' !== $pictureClass ? \sprintf('class="%s"', $pictureClass) : '';

        $html = <<<HTML
        <picture $classPictureHtml $pictureDataAttributes>
          $sourcesMarkup
          $imgMarkup
        </picture>
        HTML;

        if (!empty($htmlAlt)) {
            $html .= <<<HTML
        <div class="alt-visually-hidden" id="$id">$htmlAlt</div>
        HTML;
        }

        return $html;
    }

    public function getImageImg(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        ?string $sizes = null,
        ?string $importance = null,
        ?string $imgDataAttributes = null,
        string $htmlAlt = '',
        string $id = '',
    ): string {
        $ariaDescribedBy = '';

        if (!empty($htmlAlt)) {
            $alt = '';
            $ariaDescribedBy = 'aria-describedby="' . $id . '"';
        }

        $path = $this->processPath($path, $srcFilter);
        $classHtml = '' !== $imgClass ? \sprintf('class="%s"', $imgClass) : '';

        // data-uri support
        if ($this->isDataUriPath($path)) {
            $srcsetHtml = '';
            $srcPath = $path;
            $dimensionHtml = '';
        } else {
            $srcsetHtml = !empty($srcsetFilters)
                ? \sprintf('srcset="%s"', $this->getImageSrcset($path, $srcsetFilters))
                : '';
            $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
            $dimensionHtml = $this->getImageDimensions($path, $srcFilter);
        }

        $sizesHtml = null !== $sizes ? \sprintf('sizes="%s"', $sizes) : '';
        $importanceHtml = $this->getImportanceHtml($importance);

        return <<<HTML
  <img
    alt="$alt"
    $ariaDescribedBy
    $classHtml
    src="$srcPath"
    $srcsetHtml
    $sizesHtml
    $dimensionHtml
    $importanceHtml
    $imgDataAttributes
  />
HTML;
    }

    public function getSrcset(?string $path, array $filters): string
    {
        $path = $this->processPath($path, $filters[0]);

        return $this->getImageSrcset($path, $filters);
    }

    private function isDataUriPath(string $path): bool
    {
        return str_starts_with($path, 'data:');
    }

    private function getImageSrcset(?string $path, array $filters): string
    {
        if ($this->isDataUriPath($path)) {
            return $path;
        }

        return implode(
            ', ',
            array_map(function ($filter) use ($path) {
                return \sprintf(
                    '%s %uw',
                    $this->cacheManager->getBrowserPath($path, $filter),
                    $this->getWidthFromFilter($path, $filter),
                );
            }, $filters),
        );
    }

    /**
     * Only get the path part of the URL, stripping potential query parameters, hashes, etc.
     */
    private function cleanupPath(string $path): string
    {
        if (Path::isAbsolute($path)) {
            return parse_url($path, PHP_URL_PATH);
        }

        return $path;
    }

    private function processPath(?string $path, string $filter): string
    {
        if (!empty($path)) {
            if ($this->isDataUriPath($path)) {
                return $path;
            }

            $path = $this->cleanupPath($path);

            try {
                $this->fallbackImageResolver->resolve($filter);
                $this->dataManager->find($filter, $path);
            } catch (NotLoadableException) {
                if ($this->allowFallback) {
                    return $this->fallbackImageResolver->resolve($filter);
                }

                return $this->resolveLiipDefaultImage();
            }

            return $path;
        }

        return $this->resolveLiipDefaultImage();
    }

    private function getImageImgLazyLoad(
        string $path,
        string $srcFilter,
        ?string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        ?string $sizes = null,
        ?string $importance = null,
        ?string $imgDataAttributes = null,
        string $htmlAlt = '',
        string $id = '',
    ): string {
        $ariaDescribedBy = '';

        if (!empty($htmlAlt)) {
            $alt = '';
            $ariaDescribedBy = 'aria-describedby="' . $id . '"';
        }

        $blurStyleHtml = '';
        $classNames = [];

        // data-uri support
        if ($this->isDataUriPath($path)) {
            $srcsetHtml = '';
            $srcPath = $path;
            $dimensionHtml = '';
        } else {
            $srcsetHtml = !empty($srcsetFilters)
                ? \sprintf('srcset="%s"', $this->getImageSrcset($path, $srcsetFilters))
                : '';
            $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
            $dimensionHtml = $this->getImageDimensions($path, $srcFilter);

            // Optional blur-up: expose the placeholder as a background-image, the
            // real image is loaded natively and painted over it once ready.
            if ($this->lazyBlur && null !== $placeholderFilter) {
                $placeholderPath = $this->cacheManager->getBrowserPath($path, $placeholderFilter);
                $blurStyleHtml = \sprintf('style="background-image:url(%s)"', $placeholderPath);
                $classNames[] = $this->lazyBlurClassSelector;
            }
        }

        if ('' !== $imgClass) {
            $classNames[] = $imgClass;
        }

        $classHtml = [] !== $classNames ? \sprintf('class="%s"', implode(' ', $classNames)) : '';
        $sizesHtml = null !== $sizes ? \sprintf('sizes="%s"', $sizes) : '';
        $importanceHtml = $this->getImportanceHtml($importance);

        return <<<HTML
  <img
    alt="$alt"
    $ariaDescribedBy
    $classHtml
    src="$srcPath"
    $srcsetHtml
    loading="lazy"
    $blurStyleHtml
    $sizesHtml
    $dimensionHtml
    $importanceHtml
    $imgDataAttributes
  />
HTML;
    }

    private function getSourcesMarkup(array $sources): string
    {
        $sourcesHtml = [];

        foreach ($sources as $sourcePath => $sourceDataset) {
            $sourceFilters = $sourceDataset['filters'] ?? $sourceDataset;
            $media = '';
            $sizes = '';
            $srcSet = $this->getImageSrcset($sourcePath, $sourceFilters);
            $dimensionHtml = $this->getImageDimensions($sourcePath, current($sourceFilters));

            if (isset($sourceDataset['media'])) {
                $media = \sprintf('media="%s"', $sourceDataset['media']);
            }

            if (isset($sourceDataset['sizes'])) {
                $sizes = \sprintf('sizes="%s"', $sourceDataset['sizes']);
            }

            $sourcesHtml[] = <<<HTML
<source $media $sizes srcset="$srcSet" $dimensionHtml>
HTML;
        }

        return implode("\n", $sourcesHtml);
    }

    private function getWidthFromFilter(string $path, string $filter): int
    {
        return $this->getSizeFromFilter($path, $filter)->getWidth();
    }

    private function getImage(string $path, string $filter): ImageInterface
    {
        $binary = $this->dataManager->find($filter, $path);

        if ($binary instanceof FileBinaryInterface) {
            $image = $this->imagine->open($binary->getPath());
        } else {
            $image = $this->imagine->load($binary->getContent());
        }

        return $image;
    }

    private function getSizeFromFilter(string $path, string $filter): BoxInterface
    {
        return $this->cache->get(md5($path . $filter), function () use ($path, $filter) {
            $box = $this->getImage($path, $filter)->getSize();
            $filterConfig = $this->filters[$filter];

            if (isset($filterConfig['filters']['thumbnail']['size'])) {
                $sizes = $filterConfig['filters']['thumbnail']['size'];

                return new Box($sizes[0], $sizes[1]);
            }

            if (isset($filterConfig['filters']['fixed'])) {
                return new Box($filterConfig['filters']['fixed']['width'], $filterConfig['filters']['fixed']['height']);
            }

            if (isset($filterConfig['filters']['crop'])) {
                $sizes = $filterConfig['filters']['crop']['size'];

                return new Box($sizes[0], $sizes[1]);
            }

            if (isset($filterConfig['filters']['relative_resize']['widen'])) {
                return $box->widen($filterConfig['filters']['relative_resize']['widen']);
            }

            if (isset($filterConfig['filters']['relative_resize']['heighten'])) {
                return $box->heighten($filterConfig['filters']['relative_resize']['heighten']);
            }

            if (isset($filterConfig['filters']['relative_resize']['increase'])) {
                return $box->increase($filterConfig['filters']['relative_resize']['increase']);
            }

            if (isset($filterConfig['filters']['relative_resize']['scale'])) {
                return $box->scale($filterConfig['filters']['relative_resize']['scale']);
            }

            return $box;
        });
    }

    private function getImageDimensions(string $path, string $filter): string
    {
        if (empty($filter) || $this->isDataUriPath($path)) {
            return '';
        }

        $box = $this->getSizeFromFilter($path, $filter);

        return \sprintf('width="%s" height="%s"', $box->getWidth(), $box->getHeight());
    }

    private function getFigcaptionHtml(string $text = '', string $class = ''): string
    {
        if (!empty($text)) {
            if (!empty($class)) {
                $class = \sprintf(' class="%s"', $class);
            }

            return \sprintf('<figcaption%s>%s</figcaption>', $class, $text);
        }

        return '';
    }

    private function getImportanceHtml(?string $importance = null): string
    {
        if (null === $importance) {
            return '';
        }

        if (!\in_array($importance, ['low', 'high'], true)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'The importance %s is not valid. Only low and high are accepted.',
                    $importance,
                ),
            );
        }

        return \sprintf(' importance="%s"', $importance);
    }

    private function resolveLiipDefaultImage(): ?string
    {
        if (!$this->useLiipDefaultImage) {
            throw new \InvalidArgumentException('The path cannot be empty');
        }

        return $this->liipDefaultImage;
    }
}
