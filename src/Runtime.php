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
    private bool $useLiipDefaultImage;
    private ?string $liipDefaultImage;
    private FallbackImageResolver $fallbackImageResolver;
    private bool $allowFallback;

    public function __construct(
        CacheInterface $cache,
        CacheManager $cacheManager,
        FilterManager $filterManager,
        DataManager $dataManager,
        ImagineInterface $imagine,
        bool $useLiipDefaultImage,
        ?string $liipDefaultImage,
        FallbackImageResolver $fallbackImageResolver,
        bool $allowFallback
    ) {
        $this->cache = $cache;
        $this->cacheManager = $cacheManager;
        $this->filters = $filterManager->getFilterConfiguration()->all();
        $this->dataManager = $dataManager;
        $this->imagine = $imagine;
        $this->useLiipDefaultImage = $useLiipDefaultImage;
        $this->liipDefaultImage = $liipDefaultImage;
        $this->fallbackImageResolver = $fallbackImageResolver;
        $this->allowFallback = $allowFallback;
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
        string $imgImportance = null,
        string $figureDataAttributes = null,
        string $imgDataAttributes = null,
        string $htmlAlt = ''
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
            $id
        );

        $classFigureHtml = '' !== $figureClass ? sprintf('class="%s"', $figureClass) : '';
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
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = '100vw',
        string $figureClass = '',
        string $figcaptionText = '',
        string $figcaptionClass = '',
        string $imgImportance = null,
        string $figureDataAttributes = null,
        string $imgDataAttributes = null,
        string $htmlAlt = ''
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
            $id
        );
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
            $id
        );
        $classFigureHtml = '' !== $figureClass ? sprintf('class="%s"', $figureClass) : '';
        $figcaptionHtml = $this->getFigcaptionHtml($figcaptionText, $figcaptionClass);

        $html = <<<HTML
        <figure $classFigureHtml $figureDataAttributes>
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

    public function getImagePicture(
        ?string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = '',
        string $imgImportance = null,
        string $pictureDataAttributes = null,
        string $imgDataAttributes = null,
        string $htmlAlt = ''
    ): string {
        $path = $this->processPath($path, $srcFilter);
        $id = str_replace('.', '', uniqid('', true));

        $sourcesMarkup = $this->getSourcesMarkup($sources, false);
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
            $id
        );
        $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';

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
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        array $sources = [],
        string $alt = '',
        string $imgClass = '',
        string $pictureClass = '',
        string $importance = null,
        string $pictureDataAttributes = null,
        string $imgDataAttributes = null,
        string $htmlAlt = ''
    ): string {
        $path = $this->processPath($path, $srcFilter);
        $id = str_replace('.', '', uniqid('', true));

        $sourcesMarkup = $this->getSourcesMarkup($sources, true);
        $imgMarkup = $this->getImageImgLazyLoad(
            $path,
            $srcFilter,
            $placeholderFilter,
            $srcsetFilters,
            $alt,
            $imgClass,
            null,
            $importance,
            $imgDataAttributes
        );
        $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';

        $html = <<<HTML
        <picture $classPictureHtml $pictureDataAttributes>
                    $htmlAlt,
                    $id
                );
                $classPictureHtml = '' !== $pictureClass ? sprintf('class="%s"', $pictureClass) : '';
        
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

    public function getImageImg(
        string $path,
        string $srcFilter,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = null,
        string $importance = null,
        string $imgDataAttributes = null,
        string $htmlAlt = '',
        string $id = ''
    ): string {
        $ariaDescribedBy = '';

        if (!empty($htmlAlt)) {
            $alt = '';
            $ariaDescribedBy = 'aria-describedby="' . $id . '"';
        }

        $path = $this->processPath($path, $srcFilter);
        $classHtml = '' !== $imgClass ? sprintf('class="%s"', $imgClass) : '';
        $srcsetHtml = !empty($srcsetFilters) ?
            sprintf('srcset="%s"', $this->getImageSrcset($path, $srcsetFilters)) :
            '';
        $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $dimensionHtml = $this->getImageDimensions($path, $srcFilter);
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

    private function getImageSrcset(?string $path, array $filters): string
    {
        return implode(
            ', ',
            array_map(function ($filter) use ($path) {
                return sprintf(
                    '%s %uw',
                    $this->cacheManager->getBrowserPath($path, $filter),
                    $this->getWidthFromFilter($path, $filter)
                );
            }, $filters)
        );
    }

    private function processPath(?string $path, string $filter): string
    {
        if (!empty($path)) {
            try {
                $this->fallbackImageResolver->resolve($filter);
                $this->dataManager->find($filter, $path);
            } catch (NotLoadableException $e) {
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
        string $placeholderFilter = null,
        array $srcsetFilters = [],
        string $alt = '',
        string $imgClass = '',
        string $sizes = null,
        string $importance = null,
        string $imgDataAttributes = null,
        string $htmlAlt = '',
        string $id = ''
    ): string {
        $ariaDescribedBy = '';

        if (!empty($htmlAlt)) {
            $alt = '';
            $ariaDescribedBy = 'aria-describedby="' . $id . '"';
        }

        $srcsetHtml = !empty($srcsetFilters) ?
            sprintf('data-srcset="%s"', $this->getImageSrcset($path, $srcsetFilters)) :
            '';
        $srcPath = $this->cacheManager->getBrowserPath($path, $srcFilter);
        $sizesHtml = null !== $sizes ? sprintf('sizes="%s"', $sizes) : '';
        $placeholderPath = $this->cacheManager->getBrowserPath($path, $placeholderFilter);
        $dimensionHtml = $this->getImageDimensions($path, $srcFilter);
        $importanceHtml = $this->getImportanceHtml($importance);

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
    $importanceHtml
    $imgDataAttributes
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
            $srcSet = $this->getImageSrcset($sourcePath, $sourceFilters);
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

    private function getImportanceHtml(string $importance = null): string
    {
        if (null === $importance) {
            return '';
        }

        if (!\in_array($importance, ['low', 'high'], true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The importance %s is not valid. Only low and high are accepted.',
                    $importance
                )
            );
        }

        return sprintf(' importance="%s"', $importance);
    }

    private function resolveLiipDefaultImage(): ?string
    {
        if (!$this->useLiipDefaultImage) {
            throw new \InvalidArgumentException('The path cannot be empty');
        }

        return $this->liipDefaultImage;
    }
}
