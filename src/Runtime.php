<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Liip\ImagineBundle\Binary\FileBinaryInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Contracts\Cache\CacheInterface;

class Runtime
{
    private CacheInterface $cache;
    private CacheManager $cacheManager;
    private array $filters;
    private string $lazyLoadClassSelector;
    private string $lazyLoadPlaceholderClassSelector;
    private string $lazyBlurClassSelector;
    private DataManager $dataManager;
    private ImagineInterface $imagine;

    public function __construct(
        CacheInterface $cache,
        CacheManager $cacheManager,
        FilterManager $filterManager,
        DataManager $dataManager,
        ImagineInterface $imagine
    ) {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->filters = $filterManager->getFilterConfiguration()->all();
        $this->dataManager = $dataManager;
        $this->imagine = $imagine;
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
                $this->getWidthFromFilter($path, $filter)
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
        return $this->cache->get(md5($path.$filter), function () use ($path, $filter) {
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
        if (empty($filter)) {
            return '';
        }

        $box = $this->getSizeFromFilter($path, $filter);

        return sprintf('width="%s" height="%s"', $box->getWidth(), $box->getHeight());
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
