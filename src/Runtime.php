<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;

/**
 * Class Runtime
 */
class Runtime
{
    /** @var CacheManager */
    private $cacheManager;

    /** @var array */
    private $filters;

    /** @var string */
    private $lazyLoadClassSelector;

    /** @var string */
    private $lazyLoadPlaceholderClassSelector;

    /** @var string */
    private $lazyBlurClassSelector;

    /**
     * AppExtension constructor.
     *
     * @param CacheManager  $cacheManager
     * @param FilterManager $filterManager
     */
    public function __construct(CacheManager $cacheManager, FilterManager $filterManager)
    {
        $this->cacheManager = $cacheManager;
        $this->filters = $filterManager->getFilterConfiguration()->all();
    }

    public function setLazyLoadConfiguration(
        string $classSelector,
        string $placeholderClassSelector,
        string $lazyBlurClassSelector
    ): void {
        $this->lazyLoadClassSelector = $classSelector;
        $this->lazyLoadPlaceholderClassSelector = $placeholderClassSelector;
        $this->lazyBlurClassSelector = $lazyBlurClassSelector;
    }

    public function getUmanitImageFigure(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = '100vw',
        string $figureClass = ''
    ): string {
        $nonLazyLoadImgMarkup = $this->getNonLazyLoadImgMarkup(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes
        );
        $classFigureHtml = '' !== $figureClass ? sprintf('class="%s"', $figureClass) : '';

        return <<<HTML
<figure $classFigureHtml>
  $nonLazyLoadImgMarkup
</figure>
HTML;
    }

    public function getUmanitImageFigureLazyLoad(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = '100vw',
        string $figureClass = ''
    ): string {
        $nonLazyLoadImgMarkup = $this->getNonLazyLoadImgMarkup(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes
        );
        $imgMarkup = $this->getImgMarkup(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes
        );
        $classFigureHtml = '' !== $figureClass ? sprintf('class="%s"', $figureClass) : '';

        return <<<HTML
<figure $classFigureHtml>
  $imgMarkup
  <noscript>
    $nonLazyLoadImgMarkup
  </noscript>
</figure>
HTML;
    }

    public function getUmanitImagePicture(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = ''
    ): string {
        $sourcesMarkup = $this->getSourcesMarkup($sources, false);
        $imgMarkup = $this->getNonLazyLoadImgMarkup($path, $srcFilter, $srcsetFilters, $alt, $imgClass);
        $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';

        return <<<HTML
<picture $classPictureHtml>
  $sourcesMarkup
  $imgMarkup
</picture>
HTML;
    }

    public function getUmanitImagePictureLazyLoad(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = ''
    ): string {
        $sourcesMarkup = $this->getSourcesMarkup($sources, true);
        $imgMarkup = $this->getImgMarkup($path, $srcFilter, $placeholderFilter, $srcsetFilters, $alt, $imgClass);
        $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';

        return <<<HTML
<picture $classPictureHtml>
  $sourcesMarkup
  $imgMarkup
</picture>
HTML;
    }

    public function getUmanitImageSrcset(string $path, array $filters): string
    {
        return implode(', ', array_map(function ($filter) use ($path) {
            return sprintf(
                '%s %uw',
                $this->cacheManager->getBrowserPath($path, $filter),
                $this->getWidthFromFilter($filter)
            );
        }, $filters));
    }

    private function getImgMarkup(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = null
    ): string {
        $srcsetHtml = !empty($srcsetFilters) ?
            sprintf('data-srcset="%s"', $this->getUmanitImageSrcset($path, $srcsetFilters)) :
            '';
        try {
            $dimensionHtml = !empty($srcFilter) ?
                $this->getHeightFromFilter($srcFilter) !== 0 ?
                    sprintf('width="%s" height="%s"', $this->getWidthFromFilter($srcFilter), $this->getHeightFromFilter($srcFilter)) :
                    '':
                '';
        } catch (\LogicException $e) {
            $dimensionHtml ='';
        }
        $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $placeholderPath = $this->cacheManager->getBrowserPath($path, $placeholderFilter);

        return <<<HTML
  <img
    alt="$alt"
    class="{$this->lazyLoadClassSelector} {$this->lazyLoadPlaceholderClassSelector} {$this->lazyBlurClassSelector} $imgClass"
    src="$placeholderPath"
    data-src="$srcPath"
    $srcsetHtml
    $sizesHtml
    $dimensionHtml
  >
HTML;
    }

    private function getNonLazyLoadImgMarkup(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = null
    ): string {
        $classHtml = '' !== $imgClass ? sprintf('class="%s"', $imgClass) : '';
        $srcsetHtml = !empty($srcsetFilters) ?
            sprintf('srcset="%s"', $this->getUmanitImageSrcset($path, $srcsetFilters)) :
            '';
        try {
            $dimensionHtml = !empty($srcFilter) ?
                $this->getHeightFromFilter($srcFilter) !== 0 ?
                    sprintf('width="%s" height="%s"', $this->getWidthFromFilter($srcFilter), $this->getHeightFromFilter($srcFilter)) :
                    '':
                '';
        } catch (\LogicException $e) {
            $dimensionHtml ='';
        }
        $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';

        return <<<HTML
  <img
    alt="$alt"
    $classHtml
    src="$srcPath"
    $srcsetHtml
    $sizesHtml
    $dimensionHtml
  >
HTML;
    }

    private function getSourcesMarkup(array $sources, bool $isLazyLoad): string
    {
        $srcSetAttribute = $isLazyLoad ? 'data-srcset' : 'srcset';
        $sourcesHtml = [];

        foreach ($sources as $sourcePath => $sourceDataset) {
            $sourceFilters = $sourceDataset['filters'] ?? $sourceDataset;
            $media = '';
            $sizes = '';
            $srcSet = $this->getUmanitImageSrcset($sourcePath, $sourceFilters);

            if (isset($sourceDataset['media'])) {
                $media = sprintf('media="%s"', $sourceDataset['media']);
            }

            if (isset($sourceDataset['sizes'])) {
                $sizes = sprintf('sizes="%s"', $sourceDataset['sizes']);
            }

            $sourcesHtml[] = <<<HTML
<source $media $sizes $srcSetAttribute="$srcSet">
HTML;
        }

        return implode("\n", $sourcesHtml);
    }

    private function getWidthFromFilter(string $filter): int
    {
        $filterConfig = $this->filters[$filter];
        $width = null;

        if (isset($filterConfig['filters']['relative_resize']['widen'])) {
            $width = $filterConfig['filters']['relative_resize']['widen'];
        } elseif (isset($filterConfig['filters']['thumbnail']['size'])) {
            $width = current($filterConfig['filters']['thumbnail']['size']);
        }

        if (null === $width) {
            throw new \LogicException(
                sprintf('Can not determine the width to use for the filter "%s"', $filter)
            );
        }

        return (int) $width;
    }

    private function getHeightFromFilter(string $filter): int
    {
        $filterConfig = $this->filters[$filter];
        $height = null;

        if (isset($filterConfig['filters']['thumbnail']['size'])) {
            $height = $filterConfig['filters']['thumbnail']['size'][1];
        }

        if (null === $height) {
            throw new \LogicException(
                sprintf('Can not determine the height to use for the filter "%s"', $filter)
            );
        }

        return (int) $height;
    }
}
