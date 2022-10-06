<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Class Runtime
 */
class Runtime
{
    /** @var CacheInterface */
    private $cache;

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

    /** @var DataManager */
    private $dataManager;

    /**
     * AppExtension constructor.
     *
     * @param CacheInterface $cache
     * @param CacheManager   $cacheManager
     * @param FilterManager  $filterManager
     * @param DataManager    $dataManager
     */
    public function __construct(
        CacheInterface $cache,
        CacheManager $cacheManager,
        FilterManager $filterManager,
        DataManager $dataManager
    )
    {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->filters = $filterManager->getFilterConfiguration()->all();
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
        string $figcaptionClass = '',
        string $htmlAlt = ''
    ): string {
        $id = str_replace('.', '', uniqid('', true));

        $nonLazyLoadImgMarkup = $this->getNonLazyLoadImgMarkup(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes,
            $htmlAlt,
            $id
        );
        $classFigureHtml = '' !== $figureClass ? sprintf('class="%s"', $figureClass) : '';
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        $html = <<<HTML
<figure $classFigureHtml>
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
        string $figcaptionClass = '',
        string $htmlAlt = ''
    ): string {
        $id = str_replace('.', '', uniqid('', true));

        $nonLazyLoadImgMarkup = $this->getNonLazyLoadImgMarkup(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes,
            $htmlAlt,
            $id
        );
        $imgMarkup = $this->getImgMarkup(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            $sizes,
            $htmlAlt,
            $id
        );
        $classFigureHtml = '' !== $figureClass ? sprintf('class="%s"', $figureClass) : '';
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        $html = <<<HTML
<figure $classFigureHtml>
  $imgMarkup
  <noscript>
    $nonLazyLoadImgMarkup
  </noscript>
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

    public function getUmanitImagePicture(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = '',
        string $htmlAlt = ''
    ): string {
        $id = str_replace('.', '', uniqid('', true));

        $sourcesMarkup = $this->getSourcesMarkup($sources, false);
        $imgMarkup = $this->getNonLazyLoadImgMarkup(
            $path,
            $srcFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            null,
            $htmlAlt,
            $id
        );
        $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';

        $html = <<<HTML
<picture $classPictureHtml>
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

    public function getUmanitImagePictureLazyLoad(
        string $path,
        string $srcFilter,
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = '',
        string $htmlAlt = ''
    ): string {
        $id = str_replace('.', '', uniqid('', true));

        $sourcesMarkup = $this->getSourcesMarkup($sources, true);
        $imgMarkup = $this->getImgMarkup(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            null,
            $htmlAlt,
            $id
        );
        $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';

        $html = <<<HTML
<picture $classPictureHtml>
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
        string $sizes = null,
        string $htmlAlt = '',
        string $id = ''
    ): string {
        $ariaDescribedBy = '';

        if (!empty($htmlAlt)) {
            $alt = '';
            $ariaDescribedBy = 'aria-describedby="'.$id.'"';
        }

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
    $ariaDescribedBy
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
        string $sizes = null,
        string $htmlAlt = '',
        string $id = ''
    ): string {
        $ariaDescribedBy = '';

        if (!empty($htmlAlt)) {
            $alt = '';
            $ariaDescribedBy = 'aria-describedby="'.$id.'"';
        }

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
    $ariaDescribedBy
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
            $height = end($filterConfig['filters']['thumbnail']['size']);
        }

        if (null === $height) {
            throw new \LogicException(
                sprintf('Can not determine the height to use for the filter "%s"', $filter)
            );
        }

        return (int) $height;
    }

    private function getImageDimensions(string $path, string $filter): string
    {
        if (empty($filter)) {
            return '';
        }

        try {
            return $this->getHeightFromFilter($filter) !== 0 ?
                sprintf(
                    'width="%s" height="%s"',
                    $this->getWidthFromFilter($filter),
                    $this->getHeightFromFilter($filter)
                ) :
                '';
        } catch (\LogicException $e) {
            return $this->getOriginalImageDimensions($path, $filter);
        }
    }

    private function getOriginalImageDimensions(string $path, string $filter): string
    {
        return $this->cache->get(md5($path.$filter), function () use ($path, $filter) {
            try {
                $image = $this->dataManager->find($filter, $path);
                $sizes = getimagesizefromstring($image->getContent());

                if (false === $sizes || !isset($sizes[3])) {
                    return '';
                }

                return $sizes[3];
            } catch (\Throwable $e) {
                return '';
            }
        });
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
