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
        $this->filters      = $filterManager->getFilterConfiguration()->all();
    }

    /**
     * @param string $classSelector
     * @param string $placeholderClassSelector
     * @param string $lazyBlurClassSelector
     */
    public function setLazyLoadConfiguration(string $classSelector, string $placeholderClassSelector, string $lazyBlurClassSelector): void
    {
        $this->lazyLoadClassSelector            = $classSelector;
        $this->lazyLoadPlaceholderClassSelector = $placeholderClassSelector;
        $this->lazyBlurClassSelector            = $lazyBlurClassSelector;
    }

    /**
     * @param string $path
     * @param string $srcFilter
     * @param array  $srcsetFilters
     * @param string $alt
     * @param string $imgClass
     * @param string $sizes
     * @param string $figureClass
     *
     * @return string
     */
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

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param string|null $placeholderFilter
     * @param array       $srcsetFilters
     * @param string      $alt
     * @param string      $imgClass
     * @param string      $sizes
     * @param string      $figureClass
     *
     * @return string
     */
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
        $imgMarkup            = $this->getImgMarkup(
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

    /**
     * @param string $path
     * @param string $srcFilter
     * @param array  $srcsetFilters
     * @param array  $sources
     * @param string $alt
     * @param string $imgClass
     *
     * @return string
     */
    public function getUmanitImagePicture(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = ''
    ): string {
        $sourcesMarkup = $this->getSourcesMarkup($sources, false);
        $imgMarkup     = $this->getNonLazyLoadImgMarkup($path, $srcFilter, $srcsetFilters, $alt, $imgClass);

        return <<<HTML
<picture>
  $sourcesMarkup
  $imgMarkup
</picture>
HTML;
    }

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param string|null $placeholderFilter
     * @param array       $srcsetFilters
     * @param array       $sources
     * @param string      $alt
     * @param string      $imgClass
     *
     * @return string
     */
    public function getUmanitImagePictureLazyLoad(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = ''
    ): string {
        $sourcesMarkup = $this->getSourcesMarkup($sources, true);
        $imgMarkup     = $this->getImgMarkup($path, $srcFilter, $placeholderFilter, $srcsetFilters, $alt, $imgClass);

        return <<<HTML
<picture>
  $sourcesMarkup
  $imgMarkup
</picture>
HTML;
    }

    /**
     * @param string $path
     * @param array  $filters
     *
     * @return string
     */
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

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param string|null $placeholderFilter
     * @param array       $srcsetFilters
     * @param string      $alt
     * @param string      $imgClass
     * @param string|null $sizes
     *
     * @return string
     */
    private function getImgMarkup(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = null
    ): string {
        $srcsetHtml      = !empty($srcsetFilters) ?
            sprintf('data-srcset="%s"', $this->getUmanitImageSrcset($path, $srcsetFilters)) :
            '';
        $srcPath         = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml       = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $placeholderPath = $this->cacheManager->getBrowserPath($path, $placeholderFilter);

        return <<<HTML
  <img
    alt="$alt"
    class="{$this->lazyLoadClassSelector} {$this->lazyLoadPlaceholderClassSelector} {$this->lazyBlurClassSelector} $imgClass"
    src="$placeholderPath"
    data-src="$srcPath"
    $srcsetHtml
    $sizesHtml
  >
HTML;
    }

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param array       $srcsetFilters
     * @param string      $alt
     * @param string      $imgClass
     * @param string|null $sizes
     *
     * @return string
     */
    private function getNonLazyLoadImgMarkup(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = null
    ): string {
        $classHtml  = '' !== $imgClass ? sprintf('class="%s"', $imgClass) : '';
        $srcsetHtml = !empty($srcsetFilters) ?
            sprintf('srcset="%s"', $this->getUmanitImageSrcset($path, $srcsetFilters)) :
            '';
        $srcPath    = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml  = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';

        return <<<HTML
  <img
    alt="$alt"
    $classHtml
    src="$srcPath"
    $srcsetHtml
    $sizesHtml
  >
HTML;
    }

    /**
     * @param array $sources
     * @param bool  $isLazyLoad
     *
     * @return string
     */
    private function getSourcesMarkup(array $sources, bool $isLazyLoad): string
    {
        $srcSetAttribute = $isLazyLoad ? 'data-srcset' : 'srcset';
        $sourcesHtml     = [];

        foreach ($sources as $sourcePath => $sourceDataset) {
            $sourceFilters = $sourceDataset['filters'] ?? $sourceDataset;
            $media         = '';
            $sizes         = '';
            $srcSet        = $this->getUmanitImageSrcset($sourcePath, $sourceFilters);

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

    /**
     * @param string $filter
     *
     * @return int
     */
    private function getWidthFromFilter(string $filter): int
    {
        $filterConfig = $this->filters[$filter];
        $width        = null;

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
}
