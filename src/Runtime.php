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

    /** @var bool */
    private $lazyLoadEnabled;

    /** @var string */
    private $lazyLoadClassSelector;

    /** @var string */
    private $lazyLoadPlaceholderClassSelector;

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
     * @param bool   $enabled
     * @param string $classSelector
     * @param string $placeholderClassSelector
     */
    public function setLazyLoadConfiguration(
        bool $enabled,
        string $classSelector,
        string $placeholderClassSelector
    ): void {
        $this->lazyLoadEnabled                  = $enabled;
        $this->lazyLoadClassSelector            = $classSelector;
        $this->lazyLoadPlaceholderClassSelector = $placeholderClassSelector;
    }

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param string|null $placeholderFilter
     * @param array       $srcsetFilters
     * @param string      $alt
     * @param string      $class
     * @param string      $sizes
     *
     * @return string
     */
    public function getUmanitImageFigure(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $class = '',
        string $sizes = '100vw'
    ): string {
        $nonLazyLoadImgMarkup = $this->getNonLazyLoadImgMarkup(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $class,
            $sizes
        );

        if (!$this->lazyLoadEnabled) {
            return <<<HTML
<figure>
  $nonLazyLoadImgMarkup
</figure>
HTML;
        }

        $imgMarkup = $this->getImgMarkup(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $class,
            $sizes
        );

        return <<<HTML
<figure>
  $imgMarkup
  <noscript>
    $nonLazyLoadImgMarkup
  </noscript>
</figure>
HTML;
    }

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param string|null $placeholderFilter
     * @param array       $srcsetFilters
     * @param array       $sources
     * @param string      $alt
     * @param string      $class
     *
     * @return string
     */
    public function getUmanitImagePicture(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $class = ''
    ): string {
        $sourcesHtml = [];

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
<source $media $sizes data-srcset="$srcSet">
HTML;
        }

        $sourcesMarkup = implode("\n", $sourcesHtml);
        $imgMarkup     = $this->getImgMarkup($path, $srcFilter, $placeholderFilter, $srcsetFilters, $alt, $class);

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
        $srcset = [];

        foreach ($filters as $filter) {
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

            $srcset[] = sprintf('%s %uw', $this->cacheManager->getBrowserPath($path, $filter), $width);
        }

        return implode(', ', $srcset);
    }

    /**
     * @param string      $path
     * @param string      $srcFilter
     * @param string|null $placeholderFilter
     * @param array       $srcsetFilters
     * @param string      $alt
     * @param string      $class
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
        string $class = '',
        string $sizes = null
    ): string {
        if (!$this->lazyLoadEnabled) {
            return $this->getNonLazyLoadImgMarkup($path, $srcFilter, $srcsetFilters, $alt, $class, $sizes);
        }

        $srcsetHtml      = !empty($srcsetFilters) ?
            sprintf('srcset="%s"', $this->getUmanitImageSrcset($path, $srcsetFilters)) :
            '';
        $srcPath         = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml       = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $placeholderPath = $this->cacheManager->getBrowserPath($path, $placeholderFilter);

        return <<<HTML
  <img
    alt="$alt"
    class="{$this->lazyLoadClassSelector} {$this->lazyLoadPlaceholderClassSelector} $class"
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
     * @param string      $class
     * @param string|null $sizes
     *
     * @return string
     */
    private function getNonLazyLoadImgMarkup(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $class = '',
        string $sizes = null
    ): string {
        $classHtml  = '' !== $class ? sprintf('class="%s"', $class) : '';
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
}
