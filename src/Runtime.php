<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Contracts\Cache\CacheInterface;

class Runtime
{
    private CacheInterface $cache;
    private CacheManager $cacheManager;
    private string $lazyLoadClassSelector;
    private string $lazyLoadPlaceholderClassSelector;
    private string $lazyBlurClassSelector;
    private FilterManager $filterManager;
    private DataManager $dataManager;

    public function __construct(
        CacheInterface $cache,
        CacheManager $cacheManager,
        FilterManager $filterManager,
        DataManager $dataManager
    ) {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->filterManager = $filterManager;
        $this->dataManager = $dataManager;
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
        string $figureClass = '',
        string $figcaptionText = '',
        string $figcaptionClass = ''
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
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        return <<<HTML
<figure $classFigureHtml>
  $nonLazyLoadImgMarkup
  $figcaptionHtml
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
        string $figureClass = '',
        string $figcaptionText = '',
        string $figcaptionClass = ''
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
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        return <<<HTML
<figure $classFigureHtml>
  $imgMarkup
  <noscript>
    $nonLazyLoadImgMarkup
  </noscript>
  $figcaptionHtml
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
                $this->getImageWidth($path, $filter)
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
        $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $placeholderPath = $this->cacheManager->getBrowserPath($path, $placeholderFilter);
        $dimensionHtml = $this->getImageDimensions($path, $srcFilter);

        return <<<HTML
  <img
    alt="$alt"
    class="{$this->lazyLoadClassSelector} {$this->lazyLoadPlaceholderClassSelector} {$this->lazyBlurClassSelector} $imgClass"
    src="$placeholderPath"
    data-src="$srcPath"
    $srcsetHtml
    $sizesHtml
    $dimensionHtml
  />
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
        $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $dimensionHtml = $this->getImageDimensions($path, $srcFilter);

        return <<<HTML
  <img
    alt="$alt"
    $classHtml
    src="$srcPath"
    $srcsetHtml
    $sizesHtml
    $dimensionHtml
  />
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
            $dimensionHtml = $this->getImageDimensions($sourcePath, current($sourceFilters));

            if (isset($sourceDataset['media'])) {
                $media = sprintf('media="%s"', $sourceDataset['media']);
            }

            if (isset($sourceDataset['sizes'])) {
                $sizes = sprintf('sizes="%s"', $sourceDataset['sizes']);
            }

            $sourcesHtml[] = <<<HTML
<source $media $sizes $srcSetAttribute="$srcSet" $dimensionHtml>
HTML;
        }

        return implode("\n", $sourcesHtml);
    }

    private function getImageWidth(string $path, string $filter): int
    {
        return $this->cache->get(md5('__width__'.$path.$filter), function () use ($path, $filter) {
            try {
                return $this->getImageSizeFromString(
                    $this->getImage($path, $filter, true)->getContent()
                )['width'];
            } catch (\Throwable $e) {
                return $this->getImageSizeFromString(
                    $this->getImage($path, $filter, false)->getContent()
                )['width'];
            }
        });
    }

    private function getImageDimensions(string $path, string $filter): string
    {
        if (empty($filter)) {
            return '';
        }

        return $this->cache->get(md5($path.$filter), function () use ($path, $filter) {
            try {
                return $this->getWidthHeightHtmlString($this->getImage($path, $filter, true)->getContent());
            } catch (\Throwable $e) {
                return $this->getWidthHeightHtmlString($this->getImage($path, $filter, false)->getContent());
            }
        });
    }

    private function createFilteredBinary(string $path, string $filter): BinaryInterface
    {
        $binary = $this->dataManager->find($filter, $path);

        return $this->filterManager->applyFilter($binary, $filter);
    }

    private function getImage(string $path, string $filter, bool $applyFilter): BinaryInterface
    {
        return $applyFilter ? $this->createFilteredBinary($path, $filter) : $this->dataManager->find($filter, $path);
    }

    private function getImageSizeFromString(string $content): ?array
    {
        $sizes = getimagesizefromstring($content);

        if (false === $sizes) {
            return null;
        }

        return [
            'width'  => $sizes[0],
            'height' => $sizes[1],
            'html'   => $sizes[3],
        ];
    }

    private function getWidthHeightHtmlString(string $content): string
    {
        $sizes = $this->getImageSizeFromString($content);

        if (null === $sizes) {
            return '';
        }

        return $sizes['html'];
    }

    private function getFigcaptionHtml(string $text = '', string $class = ''): string
    {
        if (!empty($text)) {
            if (!empty($class)) {
                $class = sprintf(' class="%s"', $class);
            }

            return sprintf('<figcaption%s>%s</figcaption>', $class, $text);
        }

        return '';
    }
}
